<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes\Helpers;

use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

class AppExtension implements DynamicFunctionReturnTypeExtension
{
    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $functionReflection->getName() === 'app';
    }

    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope
    ): Type {
        $className = $this->getClassName($functionCall);

        if ($className && class_exists($className)) {
            return new ObjectType($className);
        }

        return new MixedType();
    }

    /**
     * Returns a fully qualified path to a class, or null if it is not a class.
     */
    private function getClassName(FuncCall $functionCall): ?string
    {
        if (count($functionCall->args) === 0) {
            return null;
        }

        /** @var ClassConstFetch $value */
        $value = $functionCall->args[0]->value;

        if (! $value->class instanceof FullyQualified) {
            return null;
        }

        return $value->class->toString();
    }
}
