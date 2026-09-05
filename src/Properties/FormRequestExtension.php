<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Larastan\Larastan\Reflection\FormRequestPropertyReflection;
use Larastan\Larastan\Support\FormRequestHelper;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProvider;

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

        return new FormRequestPropertyReflection($classReflection, $propertyType);
    }
}
