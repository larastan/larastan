<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Collectors;

use Illuminate\Contracts\Container\Container;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Type\ClosureType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements Collector<Node\Expr\MethodCall, array{string, ?string, int}>
 */
final class ContainerBindingsCollector implements Collector
{
    const METHODS = [
        'bind',
        'bindIf',
        'singleton',
        'singletonIf',
    ];

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param  MethodCall  $node
     * @param  Scope  $scope
     * @return ?array{string, bool, ?string, int}
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        if (! $this->isContainerBinding($node, $scope)) {
            return null;
        }

        $args = $node->getArgs();

        $concreteType = null;

        if (count($args) > 1) {
            $concreteType = $this->getConcreteType($args[1]->value, $scope);
        }

        $bindingExpr = $args[0]->value;

        // Binding ID is a class
        if ($bindingExpr instanceof Node\Expr\ClassConstFetch && $bindingExpr->class instanceof Node\Name) {
            return [
                $bindingExpr->class->toString(),
                true,
                $concreteType,
                $node->getLine(),
            ];
        }

        // Binding ID is a string
        if ($bindingExpr instanceof Node\Scalar\String_) {
            return [
                $bindingExpr->value,
                false,
                $concreteType,
                $node->getLine(),
            ];
        }

        // Unknown binding ID type
        return null;
    }

    private function isContainerBinding(MethodCall $node, Scope $scope): bool
    {
        if (! $node->name instanceof Identifier) {
            return false;
        }

        $methodName = $node->name->name;

        if (! in_array($methodName, self::METHODS, true)) {
            return false;
        }

        $calledOnType = $scope->getType($node->var);

        return (new ObjectType(Container::class))->isSuperTypeOf($calledOnType)->yes();
    }

    private function getConcreteType(Expr $expr, Scope $scope): ?string
    {
        if ($expr instanceof Node\Expr\ClassConstFetch && $expr->class instanceof Node\Name) {
            return $expr->class->toString();
        }

        if ($expr instanceof Node\FunctionLike) {
            $functionType = $scope->getType($expr);

            if ($functionType instanceof ClosureType) {
                return $functionType->getReturnType()->describe(VerbosityLevel::value());
            }
        }

        return null;
    }
}
