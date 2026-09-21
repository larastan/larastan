<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes\Helpers;

use Illuminate\Support\Stringable;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

use function count;

class StrExtension implements DynamicFunctionReturnTypeExtension
{
    private ObjectType|null $stringableType = null;

    private MixedType|null $mixedType = null;

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $functionReflection->getName() === 'str';
    }

    public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type|null
    {
        if (count($functionCall->getArgs()) === 1) {
            return $this->stringableType ??= new ObjectType(Stringable::class);
        }

        return $this->mixedType ??= new MixedType();
    }
}
