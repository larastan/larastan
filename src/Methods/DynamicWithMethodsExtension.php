<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Mail\Mailable;
use Illuminate\View\View;
use Larastan\Larastan\Reflection\DynamicWhereMethodReflection;
use Larastan\Larastan\Support\MacroHelper;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;

use function str_starts_with;

/**
 * Handles classes whose own `__call` checks for a macro first and otherwise
 * turns `withFoo($value)` into `with('foo', $value)`, returning the object.
 */
#[AutowiredService]
class DynamicWithMethodsExtension implements MethodsClassReflectionExtension
{
    private const CLASSES = [
        Mailable::class,
        RedirectResponse::class,
        View::class,
        ViewContract::class,
    ];

    public function __construct(private MacroHelper $macroHelper)
    {
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (! $this->isSupported($classReflection)) {
            return false;
        }

        if ($this->macroHelper->findMacro($classReflection, $methodName) !== null) {
            return true;
        }

        return str_starts_with($methodName, 'with');
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return $this->macroHelper->findMacro($classReflection, $methodName)
            ?? new DynamicWhereMethodReflection($classReflection, $methodName);
    }

    private function isSupported(ClassReflection $classReflection): bool
    {
        foreach (self::CLASSES as $className) {
            if ($classReflection->is($className)) {
                return true;
            }
        }

        return false;
    }
}
