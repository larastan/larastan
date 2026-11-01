<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes\Helpers;

use Closure;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;

use function count;

/** @internal */
final class ValueExtension implements DynamicFunctionReturnTypeExtension
{
    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $functionReflection->getName() === 'value';
    }

    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope,
    ): Type {
        if (count($functionCall->getArgs()) === 0) {
            return new NeverType();
        }

        $arg     = $functionCall->getArgs()[0]->value;
        $argType = $scope->getType($arg);

        return TypeTraverser::map($argType, static function (Type $type, callable $traverse) use ($scope): Type {
            if ($type instanceof UnionType) {
                return $traverse($type);
            }

            // Like value(), only call a Closure and return any other value as it is.
            $isClosure = (new ObjectType(Closure::class))->isSuperTypeOf($type);

            if ($isClosure->no() || ! $type->isCallable()->yes()) {
                return $type;
            }

            $returnType = $type->getCallableParametersAcceptors($scope)[0]->getReturnType();

            return $isClosure->yes() ? $returnType : TypeCombinator::union($type, $returnType);
        });
    }
}
