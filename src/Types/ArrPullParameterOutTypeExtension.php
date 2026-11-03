<?php

declare(strict_types=1);

namespace Larastan\Larastan\Types;

use Illuminate\Support\Arr;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\StaticMethodParameterOutTypeExtension;
use PHPStan\Type\Type;

use function str_contains;

/**
 * Removes the pulled key from the array passed by reference to Arr::pull().
 *
 * Keys in "dot" notation keep the declared type.
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
        $keyType   = $scope->getType($keyArg->value);

        if (! $arrayType->isArray()->yes()) {
            return null;
        }

        if (! $keyType->isInteger()->yes() && $keyType->getConstantStrings() === []) {
            return null;
        }

        foreach ($keyType->getConstantStrings() as $constantString) {
            if (str_contains($constantString->getValue(), '.')) {
                return null;
            }
        }

        // unset() on a list mishandles numeric strings like '0', so pass the integer key instead.
        return $arrayType->unsetOffset($keyType->toArrayKey());
    }
}
