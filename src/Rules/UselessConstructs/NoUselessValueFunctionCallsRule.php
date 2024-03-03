<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules\UselessConstructs;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ClosureType;
use PHPStan\Type\MixedType;

use function count;
use function strtolower;

/** @implements Rule<FuncCall> */
class NoUselessValueFunctionCallsRule implements Rule
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

        if (strtolower($node->name->toString()) !== 'value') {
            return [];
        }

        $args = $node->getArgs();

        if (count($args) < 1) {
            return [];
        }

        if ($scope->getType($args[0]->value)->isSuperTypeOf(new ClosureType([], new MixedType(), true))->no() === false) {
            return [];
        }

        return [
            RuleErrorBuilder::message("Calling the helper function 'value()' without a closure as the first argument simply returns the first argument without doing anything")
                ->line($node->getLine())
                ->identifier('larastan.uselessConstructs.value')
                ->build(),
        ];
    }
}
