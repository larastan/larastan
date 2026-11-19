<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Larastan\Larastan\Support\MacroHelper;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\StaticType;

/**
 * Handles the query builder, whose own `__call` checks for a macro first and
 * otherwise turns `whereFooAndBar($foo, $bar)` into where clauses, returning the builder.
 */
#[AutowiredService]
class QueryBuilderMethodsExtension implements MethodsClassReflectionExtension
{
    public function __construct(private MacroHelper $macroHelper, private BuilderHelper $builderHelper)
    {
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return $this->findMethod($classReflection, $methodName) !== null;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return $this->findMethod($classReflection, $methodName) ?? throw new ShouldNotHappenException();
    }

    private function findMethod(ClassReflection $classReflection, string $methodName): MethodReflection|null
    {
        if (! $classReflection->is(QueryBuilder::class)) {
            return null;
        }

        return $this->macroHelper->findMacro($classReflection, $methodName)
            ?? $this->builderHelper->dynamicWhere($methodName, new StaticType($classReflection));
    }
}
