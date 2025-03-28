<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes\Helpers;

use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ClosureType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;

use PHPStan\Type\TypeTraverser;
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

        $arg = $functionCall->getArgs()[0]->value;
        $argType = $scope->getType($arg);

       return TypeTraverser::map($argType, function (Type $type, callable $traverse): Type {
            if ($type instanceof ClosureType) {
                return $type->getReturnType();
            }

            return $traverse($type);
        });
    }
}
