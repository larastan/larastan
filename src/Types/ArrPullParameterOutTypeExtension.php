<?php

declare(strict_types=1);

namespace Larastan\Larastan\Types;

use Illuminate\Support\Arr;
use Larastan\Larastan\Support\ArrKeyLookup;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\StaticMethodParameterOutTypeExtension;
use PHPStan\Type\Type;

use function count;

/**
 * Removes the pulled key from the array passed by reference to Arr::pull().
 *
 * Keys in "dot" notation keep the declared type unless the literal key always exists.
 *
 * @internal
 */
final class ArrPullParameterOutTypeExtension implements StaticMethodParameterOutTypeExtension
{
    public function isStaticMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
    {
        return $methodReflection->getName() === 'pull'
            && $parameter->getName() === 'array'
            && $methodReflection->getDeclaringClass()->getName() === Arr::class;
    }

    public function getParameterOutTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        ParameterReflection $parameter,
        Scope $scope,
    ): Type|null {
        $arrayArg = $methodCall->getArg('array', 0);
        $keyArg   = $methodCall->getArg('key', 1);

        if ($arrayArg === null || $keyArg === null) {
            return null;
        }

        $arrayType = $scope->getType($arrayArg->value);

        if (! $arrayType->isArray()->yes()) {
            return null;
        }

        $lookups = ArrKeyLookup::find($scope, $arrayArg->value, $keyArg->value);

        if ($lookups === null) {
            return null;
        }

        foreach ($lookups as $candidates) {
            // Arr::forget() may remove a "dot" path instead of the literal key.
            if (count($candidates) > 1) {
                return null;
            }
        }

        // unset() on a list mishandles numeric strings like '0', so pass the integer key instead.
        return $arrayType->unsetOffset($scope->getType($keyArg->value)->toArrayKey());
    }
}
