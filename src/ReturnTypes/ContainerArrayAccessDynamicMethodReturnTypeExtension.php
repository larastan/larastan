<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use NunoMaduro\Larastan\Concerns\HasContainer;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class ContainerArrayAccessDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    use HasContainer;

    /**
     * @var string
     */
    private $className;

    public function __construct(string $className)
    {
        $this->className = $className;
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
        Scope $scope
    ): ?Type {
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
                $class = get_class($resolvedValue);

                $argTypes[] = new ObjectType($class);
                continue;
            }

            $argTypes[] = $scope->getTypeFromValue($resolvedValue);
        }

        return TypeCombinator::union(...$argTypes);
    }
}
