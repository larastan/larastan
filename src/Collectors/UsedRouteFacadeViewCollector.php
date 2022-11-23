<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Collectors;

use Illuminate\Support\Facades\Route;
use Illuminate\View\ViewName;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Type\ObjectType;

/** @implements Collector<Node\Expr\StaticCall, string> */
final class UsedRouteFacadeViewCollector implements Collector
{
    public function getNodeType(): string
    {
        return Node\Expr\StaticCall::class;
    }

    /** @param Node\Expr\StaticCall $node */
    public function processNode(Node $node, Scope $scope): ?string
    {
        $name = $node->name;

        if (! $name instanceof Node\Identifier) {
            return null;
        }

        if ($name->name !== 'view') {
            return null;
        }

        if (count($node->getArgs()) < 2) {
            return null;
        }

        $class = $node->class;

        if (! $class instanceof Node\Name) {
            return null;
        }

        $class = $scope->resolveName($class);

        if (! (new ObjectType(Route::class))->isSuperTypeOf(new ObjectType($class))->yes()) {
            return null;
        }

        $template = $node->getArgs()[1]->value;

        if (! $template instanceof Node\Scalar\String_) {
            return null;
        }

        return ViewName::normalize($template->value);
    }
}
