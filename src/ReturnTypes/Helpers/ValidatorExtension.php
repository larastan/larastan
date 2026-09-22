<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes\Helpers;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Validation\Validator;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function count;

/** @internal */
final class ValidatorExtension implements DynamicFunctionReturnTypeExtension
{
    private ObjectType|null $factoryType = null;

    private Type|null $validatorType = null;

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $functionReflection->getName() === 'validator';
    }

    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope,
    ): Type {
        if (count($functionCall->getArgs()) === 0) {
            return $this->factoryType ??= new ObjectType(Factory::class);
        }

        return $this->validatorType ??= TypeCombinator::intersect(
            new ObjectType(Validator::class),
            new ObjectType(\Illuminate\Contracts\Validation\Validator::class),
        );
    }
}
