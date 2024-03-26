<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;

/** @internal */
final class ModelAccessorExtension implements PropertiesClassReflectionExtension
{
    public function __construct(
        private ModelPropertyHelper $modelPropertyHelper,
    ) {
    }

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        return $this->modelPropertyHelper->hasAccessor($classReflection, $propertyName, strictGenerics: true);
    }

    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        return $this->modelPropertyHelper->getAccessor($classReflection, $propertyName);
    }
}
