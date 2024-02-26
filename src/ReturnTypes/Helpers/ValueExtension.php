<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes\Helpers;

use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;

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
        if ($arg instanceof Closure) {
            $callbackType = $scope->getType($arg);

            return ParametersAcceptorSelector::selectFromArgs(
                $scope,
                $functionCall->getArgs(),
                $callbackType->getCallableParametersAcceptors($scope),
            )->getReturnType();
        }

        return $scope->getType($arg);
    }
}
