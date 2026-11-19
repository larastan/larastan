<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Illuminate\Support\Fluent;
use Larastan\Larastan\Reflection\DynamicMethodReflection;
use Larastan\Larastan\Reflection\DynamicWhereParameterReflection;
use Larastan\Larastan\Reflection\ReflectionHelper;
use Larastan\Larastan\Support\MacroHelper;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\StaticType;

use function str_starts_with;

#[AutowiredService]
class FluentMethodsExtension implements MethodsClassReflectionExtension
{
    public function __construct(private MacroHelper $macroHelper)
    {
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (! $classReflection->is(Fluent::class)) {
            return false;
        }

        if ($this->macroHelper->findMacro($classReflection, $methodName) !== null) {
            return true;
        }

        return ! str_starts_with($methodName, '__') && ! ReflectionHelper::hasMethodTag($classReflection, $methodName);
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return $this->macroHelper->findMacro($classReflection, $methodName)
            ?? new DynamicMethodReflection($classReflection, $methodName, [
                new FunctionVariant(
                    TemplateTypeMap::createEmpty(),
                    null,
                    [new DynamicWhereParameterReflection()],
                    false,
                    new StaticType($classReflection),
                ),
            ]);
    }
}
