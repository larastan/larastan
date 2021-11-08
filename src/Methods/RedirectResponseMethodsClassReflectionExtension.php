<?php

namespace NunoMaduro\Larastan\Methods;

use Illuminate\Http\RedirectResponse;
use PHPStan\Reflection;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

class RedirectResponseMethodsClassReflectionExtension implements MethodsClassReflectionExtension
{
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->getName() !== RedirectResponse::class) {
            return false;
        }

        if (strpos($methodName, 'with') !== 0) {
            return false;
        }

        return true;
    }

    public function getMethod(
        ClassReflection $classReflection,
        string $methodName
    ): MethodReflection {
        return new class($classReflection, $methodName) implements MethodReflection
        {
            /** @var \PHPStan\Reflection\ClassReflection */
            private $classReflection;

            /** @var string */
            private $methodName;

            public function __construct(ClassReflection $classReflection, string $methodName)
            {
                $this->classReflection = $classReflection;
                $this->methodName = $methodName;
            }

            public function getDeclaringClass(): ClassReflection
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

            public function getPrototype(): ClassMemberReflection
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

                                public function getType(): Type
                                {
                                    return new MixedType();
                                }

                                public function passedByReference(): PassedByReference
                                {
                                    return PassedByReference::createNo();
                                }

                                public function isVariadic(): bool
                                {
                                    return false;
                                }

                                public function getDefaultValue(): ?Type
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
