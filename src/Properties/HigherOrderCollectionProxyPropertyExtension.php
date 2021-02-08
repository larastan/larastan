<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Properties;

use NunoMaduro\Larastan\Support\HigherOrderCollectionProxyHelper;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type;

class HigherOrderCollectionProxyPropertyExtension implements PropertiesClassReflectionExtension
{
    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        return HigherOrderCollectionProxyHelper::hasPropertyOrMethod($classReflection, $propertyName, 'property');
    }

    public function getProperty(
        ClassReflection $classReflection,
        string $propertyName
    ): PropertyReflection {
        $activeTemplateTypeMap = $classReflection->getActiveTemplateTypeMap();

        /** @var Type\Constant\ConstantStringType $methodType */
        $methodType = $activeTemplateTypeMap->getType('T');

        /** @var Type\ObjectType $modelType */
        $modelType = $activeTemplateTypeMap->getType('TValue');

        $propertyType = $modelType->getProperty($propertyName, new OutOfClassScope())->getReadableType();

        $returnType = HigherOrderCollectionProxyHelper::determineReturnType($methodType->getValue(), $modelType, $propertyType);

        return new class($modelType, $returnType) implements PropertyReflection {
            /** @var Type\ObjectType */
            private $modelType;

            /** @var Type\Type */
            private $returnType;

            public function __construct(Type\ObjectType $modelType, Type\Type $returnType)
            {
                $this->modelType = $modelType;
                $this->returnType = $returnType;
            }

            public function getDeclaringClass(): \PHPStan\Reflection\ClassReflection
            {
                /** @phpstan-ignore-next-line */
                return $this->modelType->getClassReflection();
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

            public function getReadableType(): Type\Type
            {
                return $this->returnType;
            }

            public function getWritableType(): Type\Type
            {
                return $this->returnType;
            }

            public function canChangeTypeAfterAssignment(): bool
            {
                return false;
            }

            public function isReadable(): bool
            {
                return true;
            }

            public function isWritable(): bool
            {
                return false;
            }

            public function isDeprecated(): \PHPStan\TrinaryLogic
            {
                return TrinaryLogic::createNo();
            }

            public function getDeprecatedDescription(): ?string
            {
                return null;
            }

            public function isInternal(): \PHPStan\TrinaryLogic
            {
                return TrinaryLogic::createNo();
            }
        };
    }
}
