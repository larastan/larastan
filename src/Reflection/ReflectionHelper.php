<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Reflection;

use PHPStan\PhpDoc\Tag\PropertyTag;
use PHPStan\Reflection\ClassReflection;

final class ReflectionHelper
{
    /**
     * Returns all property tags of the given class and its ancestors.
     *
     * In case of duplicates, the tags of the class itself have precedence.
     * TODO consider the hierarchy to ensure correct precedence between ancestors
     *
     * @return array<string, PropertyTag>
     */
    public static function collectPropertyTags(ClassReflection $classReflection): array
    {
        $allPropertyTags = $classReflection->getPropertyTags();

        foreach ($classReflection->getAncestors() as $ancestor) {
            $allPropertyTags += $ancestor->getPropertyTags();
        }

        return $allPropertyTags;
    }
}
