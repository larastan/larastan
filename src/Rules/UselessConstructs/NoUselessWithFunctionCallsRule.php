<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Rules\UselessConstructs;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ClosureType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;

/**
 * @implements Rule<FuncCall>
 */
class NoUselessWithFunctionCallsRule implements Rule
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

        if ($node->name->toString() !== 'with') {
            return [];
        }

        $args = $node->getArgs();
        if (array_key_exists(1, $args) === false) {
            return [
                RuleErrorBuilder::message("Calling the helper function 'with()' with only one argument simply returns the value itself. if you want to chain methods on a construct, use '(new ClassName())->foo()' instead")
                    ->line($node->getLine())
                    ->build()
            ];
        }

        $secondArgument = $args[1];
        $secondArgumentType = $scope->getType($secondArgument->value);
        if ($secondArgumentType->isSuperTypeOf(new ClosureType([], new MixedType(), true))->no() === false || ($secondArgumentType->isSuperTypeOf(new StringType())->no() === false && property_exists($secondArgument->value, 'value') && function_exists($secondArgument->value->value))) {
            return [];
        }

        return [
            RuleErrorBuilder::message("Calling the helper function 'with()' without a closure as the second argument simply returns the value without doing anything")
                ->line($node->getLine())
                ->build()
        ];
    }
}
