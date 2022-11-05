<?php

namespace NunoMaduro\Larastan\Methods;

use NunoMaduro\Larastan\Reflection\DynamicWhereMethodReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;

class ViewWithMethodsClassReflectionExtension implements MethodsClassReflectionExtension
{
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (! in_array($classReflection->getName(), ['Illuminate\View\View', 'Illuminate\Contracts\View\View'], true)) {
            return false;
        }

        if (! str_starts_with($methodName, 'with')) {
            return false;
        }

        return true;
    }

    public function getMethod(
        ClassReflection $classReflection,
        string $methodName
    ): MethodReflection {
        return new DynamicWhereMethodReflection($classReflection, $methodName);
    }
}
