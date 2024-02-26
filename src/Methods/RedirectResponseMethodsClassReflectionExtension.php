<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Larastan\Larastan\Reflection\DynamicWhereMethodReflection;
use PHPStan\Reflection;

use function str_starts_with;

class RedirectResponseMethodsClassReflectionExtension implements Reflection\MethodsClassReflectionExtension
{
    public function hasMethod(Reflection\ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->getName() !== 'Illuminate\Http\RedirectResponse') {
            return false;
        }

        // @phpcs:ignore
        if (! str_starts_with($methodName, 'with')) {
            return false;
        }

        return true;
    }

    public function getMethod(
        Reflection\ClassReflection $classReflection,
        string $methodName,
    ): Reflection\MethodReflection {
        return new DynamicWhereMethodReflection($classReflection, $methodName);
    }
}
