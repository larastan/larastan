<?php

namespace NunoMaduro\Larastan\Methods;

use NunoMaduro\Larastan\Reflection\DynamicWhereMethodReflection;
use PHPStan\Reflection;

class RedirectResponseMethodsClassReflectionExtension implements Reflection\MethodsClassReflectionExtension
{
    public function hasMethod(Reflection\ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->getName() !== 'Illuminate\Http\RedirectResponse') {
            return false;
        }

        if (! str_starts_with($methodName, 'with')) {
            return false;
        }

        return true;
    }

    public function getMethod(
        Reflection\ClassReflection $classReflection,
        string $methodName
    ): Reflection\MethodReflection {
        return new DynamicWhereMethodReflection($classReflection, $methodName);
    }
}
