<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Foundation\Testing\TestCase;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function count;
use function in_array;

/**
 * @internal
 */
final class TestCaseExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return TestCase::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), [
            'mock',
            'partialMock',
            'spy',
        ], true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        $defaultReturnType = new ObjectType('Mockery\\MockInterface');

        if (count($methodCall->args) === 0) {
            return new ErrorType();
        }

        $classType = $scope->getType($methodCall->getArgs()[0]->value);
        $constantStrings = $classType->getConstantStrings();

        if ($constantStrings === []) {
            return $defaultReturnType;
        }

        $returnTypes = [];

        foreach ($constantStrings as $constantString) {
            $objectType = new ObjectType($constantString->getValue());

            $returnTypes[] = TypeCombinator::intersect($defaultReturnType, $objectType);
        }

        return TypeCombinator::union(...$returnTypes);
    }
}
