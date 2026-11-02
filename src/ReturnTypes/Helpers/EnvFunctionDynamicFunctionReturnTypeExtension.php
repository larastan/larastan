<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes\Helpers;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\BooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;

use function count;

class EnvFunctionDynamicFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $functionReflection->getName() === 'env';
    }

    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope,
    ): Type|null {
        $argCount = count($functionCall->getArgs());

        if ($argCount < 2) {
            return null;
        }

        $defaultArgType = $scope->getType($functionCall->getArgs()[1]->value);

        $defaultType = TypeTraverser::map($defaultArgType, static function (Type $type, callable $traverse) use ($scope): Type {
            if ($type->isConstantScalarValue()->no() && $type->isCallable()->yes()) {
                return $type->getCallableParametersAcceptors($scope)[0]->getReturnType();
            }

            return $traverse($type);
        })->generalize(GeneralizePrecision::lessSpecific());

        // A null default says nothing about the type of a set variable, so keep the stub's bool|string|TDefault
        if ($defaultType->isNull()->yes()) {
            return TypeCombinator::union(new BooleanType(), new StringType(), $defaultType);
        }

        return $defaultType;
    }
}
