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
     * Determine if the property is read straight from a column of the model's table: unlike
     * hasProperty(), a column also described by a `@property` tag counts, an accessor does not.
     */
    public function isPlainDatabaseColumn(ClassReflection $classReflection, string $propertyName): bool
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
