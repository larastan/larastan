<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Support\Arr;
use Larastan\Larastan\Support\ArrKeyLookup;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function in_array;

/** @internal */
final class ArrGetDynamicStaticMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Arr::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        // A subclass may override the method.
        return $methodReflection->getDeclaringClass()->getName() === Arr::class
            && in_array($methodReflection->getName(), ['get', 'pull'], true);
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
    ): Type|null {
        $arrayArg = $methodCall->getArg('array', 0);
        $keyArg   = $methodCall->getArg('key', 1);

        if ($arrayArg === null || $keyArg === null) {
            return null;
        }

        $lookups = ArrKeyLookup::find($scope, $arrayArg->value, $keyArg->value);

        if ($lookups === null) {
            return null;
        }

        $types   = [];
        $mayMiss = false;

        foreach ($lookups as $candidates) {
            $exists = TrinaryLogic::createNo();

            foreach ($candidates as [$found, $access]) {
                if (! $found->no()) {
                    $types[] = $scope->getType($access);
                }

                $exists = $exists->or($found);
            }

            $mayMiss = $mayMiss || ! $exists->yes();
        }

        if ($mayMiss) {
            $defaultArg = $methodCall->getArg('default', 2);

            // Arr::get() returns value($default), which calls a Closure default.
            $types[] = $defaultArg === null
                ? new NullType()
                : $scope->getType(new FuncCall(new FullyQualified('value'), [new Arg($defaultArg->value)]));
        }

        return TypeCombinator::union(...$types);
    }
}
