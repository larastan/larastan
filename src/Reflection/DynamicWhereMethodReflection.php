<?php

declare(strict_types=1);

namespace Larastan\Larastan\Reflection;

use PHPStan\Reflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class DynamicWhereMethodReflection implements Reflection\MethodReflection
{
    public function __construct(private Reflection\ClassReflection $classReflection, private string $methodName)
    {
    }

    public function getDeclaringClass(): Reflection\ClassReflection
    {
        return $this->classReflection;
    }

    public function isStatic(): bool
    {
        return false;
    }

    public function isPrivate(): bool
    {
        return false;
    }

    public function isPublic(): bool
    {
        return true;
    }

    public function getDocComment(): string|null
    {
        return null;
    }

    public function getName(): string
    {
        return $this->methodName;
    }

    public function getPrototype(): Reflection\ClassMemberReflection
    {
        return $this;
    }

    /** @return FunctionVariant[] */
    public function getVariants(): array
    {
        return [
            new FunctionVariant(
                TemplateTypeMap::createEmpty(),
                TemplateTypeMap::createEmpty(),
                [
                    new class implements ParameterReflection
                    {
                        public function getName(): string
                        {
                            return 'dynamic-with';
                        }

                        public function isOptional(): bool
                        {
                            return false;
                        }

                        public function getType(): Type
                        {
                            return new MixedType();
                        }

                        public function passedByReference(): PassedByReference
                        {
                            return Reflection\PassedByReference::createNo();
                        }

                        public function isVariadic(): bool
                        {
                            return false;
                        }

                        public function getDefaultValue(): Type|null
                        {
                            return null;
                        }
                    },
                ],
                false,
                new ObjectType($this->classReflection->getName()),
            ),
        ];
    }

    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getDeprecatedDescription(): string|null
    {
        return null;
    }

    public function isFinal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getThrowType(): Type|null
    {
        return null;
    }

    public function hasSideEffects(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
}
