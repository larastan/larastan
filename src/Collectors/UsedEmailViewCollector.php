<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Collectors;

use Illuminate\Mail\Mailable;
use Illuminate\View\ViewName;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Type\ObjectType;

/** @implements Collector<Node\Expr\MethodCall, string> */
final class UsedEmailViewCollector implements Collector
{
    public function getNodeType(): string
    {
        return Node\Expr\MethodCall::class;
    }

    /** @param Node\Expr\MethodCall $node */
    public function processNode(Node $node, Scope $scope): ?string
    {
        $name = $node->name;

        if (! $name instanceof Identifier) {
            return null;
        }

        if (! in_array($name->name, ['markdown', 'view'], true)) {
            return null;
        }

        if (count($node->getArgs()) === 0) {
            return null;
        }

        $class = $node->var;

        if (! (new ObjectType(Mailable::class))->isSuperTypeOf($scope->getType($class))->yes()) {
            return null;
        }

        $template = $node->getArgs()[0]->value;

        if (! $template instanceof Node\Scalar\String_) {
            return null;
        }

        return ViewName::normalize($template->value);
    }
}
