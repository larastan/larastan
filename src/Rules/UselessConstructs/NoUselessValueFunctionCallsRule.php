<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Rules\UselessConstructs;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

class NoUselessValueFunctionCallsRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\FuncCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        /** @var Node\Expr\FuncCall $node */
        if ($node->name->toString() !== 'value') {
            return [];
        }

        $args = $node->args;
        if ($args[0]->value instanceof Node\Expr\Closure) {
            return [];
        }

        return ["Calling the helper function 'value()' without a closure as the first argument simply returns the first argument without doing anything"];
    }
}
