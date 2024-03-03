<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules\UselessConstructs;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

use function count;
use function strtolower;

/** @implements Rule<FuncCall> */
class NoUselessWithFunctionCallsRule implements Rule
{
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /** @return RuleError[] */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Node\Name) {
            return [];
        }

        if (strtolower($node->name->toString()) !== 'with') {
            return [];
        }

        $args = $node->getArgs();

        if (count($args) < 1) {
            return [];
        }

        if (count($args) === 1) {
            return [
                RuleErrorBuilder::message("Calling the helper function 'with()' with only one argument simply returns the value itself. If you want to chain methods on a construct, use '(new ClassName())->foo()' instead")
                    ->line($node->getLine())
                    ->identifier('larastan.uselessConstructs.with')
                    ->build(),
            ];
        }

        $secondArgumentType = $scope->getType($args[1]->value);

        if ($secondArgumentType->isCallable()->no() === false) {
            return [];
        }

        return [
            RuleErrorBuilder::message("Calling the helper function 'with()' without a callable as the second argument simply returns the value without doing anything")
                ->line($node->getLine())
                ->identifier('larastan.uselessConstructs.with')
                ->build(),
        ];
    }
}
