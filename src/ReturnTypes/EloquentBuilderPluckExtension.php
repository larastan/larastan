<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Larastan\Larastan\Support\PluckHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;

final class EloquentBuilderPluckExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(private PluckHelper $pluckHelper)
    {
    }

    public function getClass(): string
    {
        return EloquentBuilder::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'pluck';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        $valueArg = $methodCall->getArg('column', 0);
        $keyArg   = $methodCall->getArg('key', 1);

        if ($valueArg === null) {
            return null;
        }

        $from = $scope->getType($methodCall->var)->getTemplateType(EloquentBuilder::class, 'TModel');

        return $this->pluckHelper->getCollectionType($from, $valueArg, $keyArg, $scope);
    }
}
