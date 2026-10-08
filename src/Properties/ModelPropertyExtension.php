<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;

/** @internal */
final class ModelPropertyExtension implements PropertiesClassReflectionExtension
{
    public function __construct(
        private ModelPropertyHelper $modelPropertyHelper,
    ) {
    }

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if ($this->modelPropertyHelper->hasAccessor($classReflection, $propertyName, strictGenerics: false)) {
            return false;
        }

        return $this->modelPropertyHelper->hasDatabaseProperty($classReflection, $propertyName);
    }

    /**
     * Determine if the property is a plain database column: it exists in the table of the model,
     * is not shadowed by an accessor, and may or may not be described by a `@property` tag.
     */
    public function hasDatabaseColumn(ClassReflection $classReflection, string $propertyName): bool
    {
        if ($this->modelPropertyHelper->hasAccessor($classReflection, $propertyName, strictGenerics: false)) {
            return false;
        }

        return $this->modelPropertyHelper->hasDatabaseColumn($classReflection, $propertyName);
    }

    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        return $this->modelPropertyHelper->getDatabaseProperty($classReflection, $propertyName);
    }
}
