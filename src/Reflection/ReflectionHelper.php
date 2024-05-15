<?php

declare(strict_types=1);

namespace Larastan\Larastan\Reflection;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Mixin\MixinMethodsClassReflectionExtension;
use PHPStan\Reflection\Mixin\MixinPropertiesClassReflectionExtension;

use function array_key_exists;

final class ReflectionHelper
{
    private static MixinPropertiesClassReflectionExtension|null $mixinPropertiesClassReflectionExtension = null;

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

        if (self::$mixinPropertiesClassReflectionExtension === null) {
            /** @phpstan-ignore-next-line */
            self::$mixinPropertiesClassReflectionExtension = (new MixinPropertiesClassReflectionExtension([]));
        }

        /** @phpstan-ignore-next-line */
        return self::$mixinPropertiesClassReflectionExtension
            ->hasProperty($classReflection, $propertyName);
    }

    private static MixinMethodsClassReflectionExtension|null $mixinMethodsClassReflectionExtension = null;

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

        if (self::$mixinMethodsClassReflectionExtension === null) {
            /** @phpstan-ignore-next-line */
            self::$mixinMethodsClassReflectionExtension = new MixinMethodsClassReflectionExtension([]);
        }

        /** @phpstan-ignore-next-line */
        return self::$mixinMethodsClassReflectionExtension
            ->hasMethod($classReflection, $methodName);
    }
}
