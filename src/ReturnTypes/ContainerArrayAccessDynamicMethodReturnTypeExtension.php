<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Larastan\Larastan\Concerns\HasContainer;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function count;
use function is_object;

class ContainerArrayAccessDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    use HasContainer;

    public function __construct(private string $className)
    {
    }

    public function getClass(): string
    {
        return $this->className;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'offsetGet';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        $args = $methodCall->getArgs();

        if (count($args) === 0) {
            return null;
        }

        $argType = $scope->getType($args[0]->value);

        $argStrings = $argType->getConstantStrings();

        if ($argStrings === []) {
            return null;
        }

        $argTypes = [];

        foreach ($argStrings as $argString) {
            $resolvedValue = $this->resolve($argString->getValue());

            if ($resolvedValue === null) {
                $argTypes[] = new ErrorType();
                continue;
            }

            if (is_object($resolvedValue)) {
                $class = $resolvedValue::class;

                $argTypes[] = new ObjectType($class);
                continue;
            }

            $argTypes[] = $scope->getTypeFromValue($resolvedValue);
        }

        return TypeCombinator::union(...$argTypes);
    }
}
