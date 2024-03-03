<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use Illuminate\Database\Eloquent\Collection;
use Larastan\Larastan\Support\HigherOrderCollectionProxyHelper;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type;

final class HigherOrderCollectionProxyPropertyExtension implements PropertiesClassReflectionExtension
{
    public function __construct(private HigherOrderCollectionProxyHelper $higherOrderCollectionProxyHelper)
    {
    }

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        return $this->higherOrderCollectionProxyHelper->hasPropertyOrMethod($classReflection, $propertyName, 'property');
    }

    public function getProperty(
        ClassReflection $classReflection,
        string $propertyName,
    ): PropertyReflection {
        $activeTemplateTypeMap = $classReflection->getActiveTemplateTypeMap();

        /** @var Type\Constant\ConstantStringType $methodType */
        $methodType = $activeTemplateTypeMap->getType('T');

        /** @var Type\ObjectType $modelType */
        $modelType = $activeTemplateTypeMap->getType('TValue');

        /** @var Type\Type $collectionType */
        $collectionType = $activeTemplateTypeMap->getType('TCollection');

        $propertyType = $modelType->getProperty($propertyName, new OutOfClassScope())->getReadableType();

        if ($collectionType->getObjectClassNames() !== []) {
            $collectionClassName = $collectionType->getObjectClassNames()[0];
        } else {
            $collectionClassName = Collection::class;
        }

        $returnType = $this->higherOrderCollectionProxyHelper->determineReturnType($methodType->getValue(), $modelType, $propertyType, $collectionClassName);

        return new class ($classReflection, $returnType) implements PropertyReflection
        {
            public function __construct(private ClassReflection $classReflection, private Type\Type $returnType)
            {
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

            public function getDocComment(): string|null
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

            public function isDeprecated(): TrinaryLogic
            {
                return TrinaryLogic::createNo();
            }

            public function getDeprecatedDescription(): string|null
            {
                return null;
            }

            public function isInternal(): TrinaryLogic
            {
                return TrinaryLogic::createNo();
            }
        };
    }
}
