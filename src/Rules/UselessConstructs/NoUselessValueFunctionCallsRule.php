<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Rules\UselessConstructs;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<FuncCall>
 */
class NoUselessValueFunctionCallsRule implements Rule
{
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        /** @var FuncCall $node */
        if (! $node->name instanceof Node\Name) {
            return [];
        }

        if ($node->name->toString() !== 'value') {
            return [];
        }

        $args = $node->getArgs();
        if ($args[0]->value instanceof Node\Expr\Closure) {
            return [];
        }

        return ["Calling the helper function 'value()' without a closure as the first argument simply returns the first argument without doing anything"];
    }
}
