<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Reflection;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Mixin\MixinMethodsClassReflectionExtension;
use PHPStan\Reflection\Mixin\MixinPropertiesClassReflectionExtension;

final class ReflectionHelper
{
    /**
     * Does the given class or any of its ancestors have an `@property*` annotation with the given name?
     */
    public static function hasPropertyTag(ClassReflection $classReflection, string $propertyName): bool
    {
        if (array_key_exists($propertyName, $classReflection->getPropertyTags())) {
            return true;
        }

        foreach ($classReflection->getAncestors() as $ancestor) {
            if (array_key_exists($propertyName, $ancestor->getPropertyTags())) {
                return true;
            }
        }

        return (new MixinPropertiesClassReflectionExtension([]))->hasProperty($classReflection, $propertyName); // @phpstan-ignore-line
    }

    /**
     * Does the given class or any of its ancestors have an `@method*` annotation with the given name?
     */
    public static function hasMethodTag(ClassReflection $classReflection, string $methodName): bool
    {
        if (array_key_exists($methodName, $classReflection->getMethodTags())) {
            return true;
        }

        foreach ($classReflection->getAncestors() as $ancestor) {
            if (array_key_exists($methodName, $ancestor->getMethodTags())) {
                return true;
            }
        }

        return (new MixinMethodsClassReflectionExtension([]))->hasMethod($classReflection, $methodName); // @phpstan-ignore-line
    }
}
