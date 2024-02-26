<?php

declare(strict_types=1);

namespace Larastan\Larastan\Collectors;

use Illuminate\Support\Facades\View;
use Illuminate\View\ViewName;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Type\ObjectType;

use function count;

/** @implements Collector<Node\Expr\StaticCall, string> */
final class UsedViewFacadeMakeCollector implements Collector
{
    public function getNodeType(): string
    {
        return Node\Expr\StaticCall::class;
    }

    /** @param Node\Expr\StaticCall $node */
    public function processNode(Node $node, Scope $scope): string|null
    {
        $name = $node->name;

        if (! $name instanceof Node\Identifier) {
            return null;
        }

        if ($name->name !== 'make') {
            return null;
        }

        if (count($node->getArgs()) < 1) {
            return null;
        }

        $class = $node->class;

        if (! $class instanceof Node\Name) {
            return null;
        }

        $class = $scope->resolveName($class);

        if (! (new ObjectType(View::class))->isSuperTypeOf(new ObjectType($class))->yes()) {
            return null;
        }

        $template = $node->getArgs()[0]->value;

        if (! $template instanceof Node\Scalar\String_) {
            return null;
        }

        return ViewName::normalize($template->value);
    }
}
