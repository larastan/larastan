<?php

declare(strict_types = 1);

namespace NunoMaduro\Larastan\Types;

use NunoMaduro\Larastan\Properties\ModelPropertyExtension;
use PHPStan\Broker\Broker;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

class GenericModelPropertyType extends ModelPropertyType
{
    /** @var Type */
    private $type;

    /** @var ModelPropertyExtension */
    protected $propertyExtension;

    public function __construct(Type $type, ModelPropertyExtension $propertyExtension)
    {
        $this->type = $type;
        $this->propertyExtension = $propertyExtension;
    }

    public function getReferencedClasses(): array
    {
        return $this->getGenericType()->getReferencedClasses();
    }

    public function getGenericType(): Type
    {
        return $this->type;
    }

    public function describe(VerbosityLevel $level): string
    {
        return sprintf('%s<%s>', parent::describe($level), $this->getGenericType()->describe($level));
    }

    public function accepts(Type $type, bool $strictTypes): TrinaryLogic
    {
        if ($type instanceof CompoundType) {
            return $type->isAcceptedBy($this, $strictTypes);
        }

        if ($type instanceof ConstantStringType) {
            $genericType = $this->getGenericType();

            if ($genericType instanceof UnionType || $genericType instanceof IntersectionType) {
                $types = $genericType->getTypes();
                /** @var Type $lastType */
                $lastType = array_pop($types);
                $newGenericType1 =  new self($lastType, $this->propertyExtension);
                if ($genericType instanceof UnionType) {
                    $newGenericType2 = (new self(count($types) > 1 ? new UnionType($types) : $types[0], $this->propertyExtension));

                    return TrinaryLogic::createNo()->or(
                        $newGenericType1->accepts($type, $strictTypes),
                        $newGenericType2->accepts($type, $strictTypes)
                );
                }
                $newGenericType2 = (new self(count($types) > 1 ? new IntersectionType($types) : $types[0], $this->propertyExtension));

                if ($newGenericType1->accepts($type, $strictTypes)->no() || $newGenericType2->accepts($type, $strictTypes)->no()) {
                    return TrinaryLogic::createNo();
                }

                return TrinaryLogic::createYes();
            }

            if ($genericType instanceof MixedType) {
                return TrinaryLogic::createYes();
            }

            if (! $genericType instanceof ObjectType) {
                return TrinaryLogic::createNo();
            }

            $classReflection = $genericType->getClassReflection();


            if ($classReflection === null) {
                return TrinaryLogic::createNo();
            }

            return TrinaryLogic::createFromBoolean(
                $this->propertyExtension->hasProperty($classReflection, $type->getValue())
            );
        } elseif ($type instanceof self) {
            $objectType = $type->type;
        } elseif ($type instanceof ClassStringType) {
            $objectType = new ObjectWithoutClassType();
        } elseif ($type instanceof StringType) {
            return TrinaryLogic::createMaybe();
        } else {
            return TrinaryLogic::createNo();
        }

        return $this->getGenericType()->accepts($objectType, $strictTypes);
    }

    public function isSuperTypeOf(Type $type): TrinaryLogic
    {
        if ($type instanceof CompoundType) {
            return $type->isSubTypeOf($this);
        }

        if ($type instanceof ConstantStringType) {
            $genericType = $this->getGenericType();
            if ($genericType instanceof MixedType) {
                return TrinaryLogic::createYes();
            }

            if ($genericType instanceof StaticType) {
                $genericType = $genericType->getStaticObjectType();
            }

            // We are transforming constant class-string to ObjectType. But we need to filter out
            // an uncertainty originating in possible ObjectType's class subtypes.
            $objectType = new ObjectType($type->getValue());

            // Do not use TemplateType's isSuperTypeOf handling directly because it takes ObjectType
            // uncertainty into account.
            if ($genericType instanceof TemplateType) {
                $isSuperType = $genericType->getBound()->isSuperTypeOf($objectType);
            } else {
                $isSuperType = $genericType->isSuperTypeOf($objectType);
            }

            // Explicitly handle the uncertainty for Maybe.
            if ($isSuperType->maybe()) {
                return TrinaryLogic::createNo();
            }

            return $isSuperType;
        } elseif ($type instanceof self) {
            return $this->getGenericType()->isSuperTypeOf($type->type);
        } elseif ($type instanceof StringType) {
            return TrinaryLogic::createMaybe();
        }

        return TrinaryLogic::createNo();
    }

    public function traverse(callable $cb): Type
    {
        $newType = $cb($this->getGenericType());
        if ($newType === $this->getGenericType()) {
            return $this;
        }

        return new self($newType, $this->propertyExtension);
    }

    public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
    {
        if ($receivedType instanceof UnionType || $receivedType instanceof IntersectionType) {
            return $receivedType->inferTemplateTypesOn($this);
        }

        if ($receivedType instanceof ConstantStringType) {
            $typeToInfer = new ObjectType($receivedType->getValue());
        } elseif ($receivedType instanceof self) {
            $typeToInfer = $receivedType->type;
        } elseif ($receivedType instanceof ClassStringType) {
            $typeToInfer = $this->getGenericType();
            if ($typeToInfer instanceof TemplateType) {
                $typeToInfer = $typeToInfer->getBound();
            }

            $typeToInfer = TypeCombinator::intersect($typeToInfer, new ObjectWithoutClassType());
        } else {
            return TemplateTypeMap::createEmpty();
        }

        if (!$this->getGenericType()->isSuperTypeOf($typeToInfer)->no()) {
            return $this->getGenericType()->inferTemplateTypes($typeToInfer);
        }

        return TemplateTypeMap::createEmpty();
    }

    public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
    {
        $variance = $positionVariance->compose(TemplateTypeVariance::createCovariant());

        return $this->getGenericType()->getReferencedTemplateTypes($variance);
    }

    /**
     * @param mixed[] $properties
     * @return Type
     */
    public static function __set_state(array $properties): Type
    {
        return new self($properties['type'], $properties['extension']);
    }

}