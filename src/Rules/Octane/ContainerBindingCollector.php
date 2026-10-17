<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules\Octane;

use Illuminate\Contracts\Container\Container;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Type\ObjectType;

use function count;
use function in_array;
use function strtolower;

/**
 * Collect statically known container registrations across analysed files.
 * Dynamic abstracts and closures are not interpreted as class constructors.
 *
 * @internal
 *
 * @implements Collector<MethodCall, array{string, string, string|null, int}>
 */
final class ContainerBindingCollector implements Collector
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /** @return array{string, string, string|null, int}|null */
    public function processNode(Node $node, Scope $scope): array|null
    {
        if (! $node->name instanceof Node\Identifier || $node->isFirstClassCallable()) {
            return null;
        }

        $method = strtolower($node->name->name);

        if (! in_array($method, ['bind', 'scoped', 'scopedif', 'singleton', 'singletonif', 'instance'], true)) {
            return null;
        }

        if (! (new ObjectType(Container::class))->isSuperTypeOf($scope->getType($node->var))->yes()) {
            return null;
        }

        $abstractArg = null;
        $concreteArg = null;

        foreach ($node->getArgs() as $index => $arg) {
            if ($arg->unpack) {
                return null;
            }

            $name = $arg->name?->name;

            if ($name === 'abstract' || ($name === null && $index === 0)) {
                $abstractArg = $arg->value;
            } elseif ($name === 'concrete' || ($name === null && $index === 1)) {
                $concreteArg = $arg->value;
            }
        }

        $abstract = $this->className($abstractArg, $scope);

        if ($abstract === null) {
            return null;
        }

        // An omitted concrete is Laravel's self-binding. A factory or an
        // existing instance is not: its constructor must never be guessed.
        $concrete = $abstract;

        if ($method === 'instance') {
            $concrete = null;
        } elseif ($concreteArg !== null && ! ($concreteArg instanceof Expr\ConstFetch && strtolower($concreteArg->name->toString()) === 'null')) {
            $concrete = $this->className($concreteArg, $scope);
        }

        return [$method, $abstract, $concrete, $node->getStartLine()];
    }

    private function className(Expr|null $expression, Scope $scope): string|null
    {
        if (
            ! $expression instanceof ClassConstFetch
            || ! $expression->name instanceof Node\Identifier
            || strtolower($expression->name->name) !== 'class'
            || ! $expression->class instanceof Name
        ) {
            return null;
        }

        $classes = $scope->resolveTypeByName($expression->class)->getObjectClassReflections();

        return count($classes) === 1 ? $classes[0]->getName() : null;
    }
}
