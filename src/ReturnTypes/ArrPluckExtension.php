<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Support\Arr;
use Larastan\Larastan\Support\PluckHelper;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Type;

final class ArrPluckExtension implements DynamicStaticMethodReturnTypeExtension
{
    public function __construct(private PluckHelper $pluckHelper)
    {
    }

    public function getClass(): string
    {
        return Arr::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'pluck';
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
    ): Type|null {
        $arrayArg = $methodCall->getArg('array', 0);
        $valueArg = $methodCall->getArg('value', 1);
        $keyArg   = $methodCall->getArg('key', 2);

        if ($arrayArg === null || $valueArg === null) {
            return null;
        }

        $from = $scope->getType($arrayArg->value)->getIterableValueType();

        return $this->pluckHelper->getArrayType($from, $valueArg, $keyArg, $scope);
    }
}
