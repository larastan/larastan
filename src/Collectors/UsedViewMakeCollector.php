<?php

declare(strict_types=1);

namespace Larastan\Larastan\Collectors;

use Illuminate\Contracts\View\Factory;
use Illuminate\View\ViewName;
use Larastan\Larastan\Support\CallArgumentsHelper;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\ObjectType;

/** @implements Collector<Node\Expr\MethodCall, string> */
#[AutowiredService]
final class UsedViewMakeCollector implements Collector
{
    public function __construct(private CallArgumentsHelper $callArgumentsHelper)
    {
    }

    public function getNodeType(): string
    {
        return Node\Expr\MethodCall::class;
    }

    /** @param Node\Expr\MethodCall $node */
    public function processNode(Node $node, Scope $scope): string|null
    {
        $name = $node->name;

        if (! $name instanceof Node\Identifier) {
            return null;
        }

        if ($name->name !== 'make') {
            return null;
        }

        $class = $node->var;

        if (! (new ObjectType(Factory::class))->isSuperTypeOf($scope->getType($class))->yes()) {
            return null;
        }

        $args = $this->callArgumentsHelper->ofMethodCall($node, $scope);

        if (! isset($args[0])) {
            return null;
        }

        $template = $args[0]->value;

        if (! $template instanceof Node\Scalar\String_) {
            return null;
        }

        return ViewName::normalize($template->value);
    }
}
