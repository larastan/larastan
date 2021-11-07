<?php

namespace NunoMaduro\Larastan\Methods;

use PHPStan\Reflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

class RedirectResponseMethodsClassReflectionExtension implements Reflection\MethodsClassReflectionExtension
{
    public function hasMethod(Reflection\ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->getName() !== 'Illuminate\Http\RedirectResponse') {
            return false;
        }

        if (strpos($methodName, 'with') !== 0) {
            return false;
        }

        return true;
    }

    public function getMethod(
        Reflection\ClassReflection $classReflection,
        string $methodName
    ): Reflection\MethodReflection {
        return new class($classReflection, $methodName) implements Reflection\MethodReflection
        {
            /** @var Reflection\ClassReflection */
            private $classReflection;

            /** @var string */
            private $methodName;

            public function __construct(Reflection\ClassReflection $classReflection, string $methodName)
            {
                $this->classReflection = $classReflection;
                $this->methodName = $methodName;
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

            public function getDocComment(): ?string
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

                                public function getType(): \PHPStan\Type\Type
                                {
                                    return new MixedType();
                                }

                                public function passedByReference(): \PHPStan\Reflection\PassedByReference
                                {
                                    return Reflection\PassedByReference::createNo();
                                }

                                public function isVariadic(): bool
                                {
                                    return false;
                                }

                                public function getDefaultValue(): ?\PHPStan\Type\Type
                                {
                                    return null;
                                }
                            },
                        ],
                        false,
                        new ObjectType($this->classReflection->getName())
                    ),
                ];
            }

            public function isDeprecated(): TrinaryLogic
            {
                return TrinaryLogic::createNo();
            }

            public function getDeprecatedDescription(): ?string
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

            public function getThrowType(): ?Type
            {
                return null;
            }

            public function hasSideEffects(): TrinaryLogic
            {
                return TrinaryLogic::createNo();
            }
        };
    }
}
