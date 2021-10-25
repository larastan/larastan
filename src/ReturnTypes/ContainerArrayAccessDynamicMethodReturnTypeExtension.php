<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use NunoMaduro\Larastan\Concerns\HasContainer;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ObjectType;

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
    ): \PHPStan\Type\Type {
        $args = $methodCall->getArgs();

        if (count($args) === 0) {
            return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        }

        $argType = $scope->getType($args[0]->value);

        if (! $argType instanceof ConstantStringType) {
            return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        }

        $resolvedObject = $this->resolve($argType->getValue());

        if ($resolvedObject === null) {
            return new ErrorType();
        }

        $class = get_class($resolvedObject);

        if ($class === false) {
            return new ErrorType();
        }

        return new ObjectType($class);
    }
}
