<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Larastan\Larastan\Support\FormRequestHelper;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

class FormRequestExtension implements PropertiesClassReflectionExtension
{
    public function __construct(private ReflectionProvider $reflectionProvider, private FormRequestHelper $formRequestHelper)
    {
    }

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if (! $classReflection->isSubclassOfClass($this->reflectionProvider->getClass(FormRequest::class))) {
            return false;
        }

        return $this->formRequestHelper->hasProperty($classReflection, $propertyName);
    }

    public function getProperty(
        ClassReflection $classReflection,
        string $propertyName,
    ): PropertyReflection {
        $propertyType = $this->formRequestHelper->getProperty($classReflection, $propertyName);

        return new class ($classReflection, $propertyName, $propertyType) implements PropertyReflection {
            public function __construct(
                private ClassReflection $classReflection,
                private string $propertyName,
                private Type $type,
            ) {
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

            public function getReadableType(): Type
            {
                return $this->type;
            }

            public function getWritableType(): Type
            {
                return $this->type;
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
                return true;
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
