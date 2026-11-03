<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Support\Arr;
use Larastan\Larastan\Support\ArrKeyLookup;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

use function count;
use function in_array;
use function strtolower;

/**
 * Reports Arr::get() and Arr::pull() keys that never exist, through PHPStan's own offset check.
 *
 * @implements Rule<StaticCall>
 */
final class ArrKeyExistenceRule implements Rule
{
    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /**
     * @param Scope&NodeCallbackInvoker $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Identifier || ! $node->class instanceof Name) {
            return [];
        }

        if (! in_array(strtolower($node->name->name), ['get', 'pull'], true)) {
            return [];
        }

        $method = $scope->getMethodReflection($scope->resolveTypeByName($node->class), $node->name->name);

        // A subclass may override the method.
        if ($method === null || $method->getDeclaringClass()->getName() !== Arr::class) {
            return [];
        }

        $arrayArg = $node->getArg('array', 0);
        $keyArg   = $node->getArg('key', 1);

        if ($arrayArg === null || $keyArg === null) {
            return [];
        }

        $lookups = ArrKeyLookup::find($scope, $arrayArg->value, $keyArg->value) ?? [];

        foreach ($lookups as $candidates) {
            foreach ($candidates as [$found]) {
                if (! $found->no()) {
                    return [];
                }
            }
        }

        foreach ($lookups as $candidates) {
            // The last candidate is the "dot" path when there is one.
            [, $missing] = $candidates[count($candidates) - 1];
            $missing->setAttributes($node->getAttributes());
            $scope->invokeNodeCallback($missing);
        }

        return [];
    }
}
