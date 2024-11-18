<?php

declare(strict_types=1);

namespace Larastan\Larastan\Types\ModelProperty;

use Larastan\Larastan\Properties\ModelPropertyHelper;
use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeReference;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

use function count;
use function explode;
use function sprintf;
use function str_contains;

class GenericModelPropertyType extends StringType
{
    public function __construct(private Type $type, private ModelPropertyHelper $modelPropertyHelper)
    {
        parent::__construct();
    }

    public function describe(VerbosityLevel $level): string
    {
        return 'model property of ' . $this->type->describe($level);
    }

    /** @return string[] */
    public function getReferencedClasses(): array
    {
        return $this->getGenericType()->getReferencedClasses();
    }

    public function getGenericType(): Type
    {
        return $this->type;
    }

    public function acceptsWithReason(Type $type, bool $strictTypes): AcceptsResult
    {
        if ($type instanceof CompoundType) {
            return $type->isAcceptedWithReasonBy($this, $strictTypes);
        }

        if (count($type->getConstantStrings()) === 1) {
            $givenString = $type->getConstantStrings()[0]->getValue();
            $genericType = $this->getGenericType();

            if ($genericType instanceof MixedType) {
                return AcceptsResult::createYes();
            }

            if (count($genericType->getObjectClassNames()) < 1) {
                return AcceptsResult::createYes();
            }

            if (str_contains($givenString, ' as ')) {
                $givenString = explode(' as ', $givenString)[0];
            }

            if (str_contains($givenString, '.')) {
                $parts = explode('.', $givenString);

                if (count($parts) !== 2) {
                    return AcceptsResult::createYes();
                }

                [$tableName, $propertyName] = $parts;

                if (! $this->modelPropertyHelper->hasDatabaseProperty($tableName, $propertyName)) {
                    return AcceptsResult::createNo([sprintf('Database table "%s" does not have column "%s"', $tableName, $propertyName)]);
                }

                return AcceptsResult::createYes();
            }

            $reasons = [];

            if (! $genericType->hasProperty($givenString)->yes()) {
                $reasons[] = sprintf('The given string should be a property of %s, %s given.', $this->type->describe(VerbosityLevel::value()), $givenString);
            }

            return new AcceptsResult($genericType->hasProperty($givenString), $reasons);
        }

        if ($type instanceof self) {
            return new AcceptsResult($this->getGenericType()->accepts($type->getGenericType(), $strictTypes), [sprintf('The given string should be a property of %s', $this->type->describe(VerbosityLevel::value()))]);
        }

        if ($type->isString()->yes()) {
            // It is an arbitrary string, so we cannot check if it is a property of the model.
            // We return yes to not cause issues on levels 7+
            return AcceptsResult::createYes();
        }

        return AcceptsResult::createNo();
    }

    public function isSuperTypeOf(Type $type): TrinaryLogic
    {
        $constantStrings = $type->getConstantStrings();

        if (count($constantStrings) === 1) {
            if (! $this->getGenericType()->hasProperty($constantStrings[0]->getValue())->yes()) {
                return TrinaryLogic::createNo();
            }

            return TrinaryLogic::createYes();
        }

        if ($type instanceof self) {
            return $this->getGenericType()->isSuperTypeOf($type->getGenericType());
        }

        if ($type instanceof CompoundType) {
            return $type->isSubTypeOf($this);
        }

        return TrinaryLogic::createNo();
    }

    public function traverse(callable $cb): Type
    {
        $newType = $cb($this->getGenericType());

        if ($newType === $this->getGenericType()) {
            return $this;
        }

        return new self($newType, $this->modelPropertyHelper);
    }

    public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
    {
        if ($receivedType instanceof UnionType || $receivedType instanceof IntersectionType) { // @phpstan-ignore-line
            return $receivedType->inferTemplateTypesOn($this);
        }

        $constantStrings = $receivedType->getConstantStrings();

        if (count($constantStrings) === 1) {
            $typeToInfer = new ObjectType($constantStrings[0]->getValue());
        } elseif ($receivedType instanceof self) {
            $typeToInfer = $receivedType->type;
        } elseif ($receivedType->isClassStringType()->yes()) {
            $typeToInfer = $this->getGenericType();

            if ($typeToInfer instanceof TemplateType) {
                $typeToInfer = $typeToInfer->getBound();
            }

            $typeToInfer = TypeCombinator::intersect($typeToInfer, new ObjectWithoutClassType());
        } else {
            return TemplateTypeMap::createEmpty();
        }

        if (! $this->getGenericType()->isSuperTypeOf($typeToInfer)->no()) {
            return $this->getGenericType()->inferTemplateTypes($typeToInfer);
        }

        return TemplateTypeMap::createEmpty();
    }

    /** @return TemplateTypeReference[] */
    public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
    {
        $variance = $positionVariance->compose(TemplateTypeVariance::createCovariant());

        return $this->getGenericType()->getReferencedTemplateTypes($variance);
    }

    /** @param  mixed[] $properties */
    public static function __set_state(array $properties): Type
    {
        return new self($properties['type'], $properties['modelPropertyHelper']);
    }
}
