<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Rules\UselessConstructs;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

class NoUselessWithFunctionCallsRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\FuncCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        /** @var Node\Expr\FuncCall $node */
        if ($node->name->toString() !== 'with') {
            return [];
        }

        $args = $node->args;
        if (count($args) === 1) {
            return ["Calling the helper function 'with()' with only one argument simply returns the value itself. if you want to chain methods on a construct, use '(new ClassName())->foo()' instead"];
        }

        if ($args[1]->value instanceof Node\Expr\Closure || ($args[1]->value instanceof Node\Scalar\String_ && function_exists($args[1]->value->value))) {
            return [];
        }

        return ["Calling the helper function 'with()' without a closure as the second argument simply returns the value without doing anything"];
    }
}
