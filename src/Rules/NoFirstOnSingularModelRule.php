<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Database\Eloquent\Model;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

/** @implements Rule<MethodCall> */
final class NoFirstOnSingularModelRule implements Rule
{
    /**
     * Specifies the node type this rule is interested in.
     * We are interested in method calls.
     *
     * @return class-string<Node>
     */
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * Processes a MethodCall node to check for redundant ->first() calls.
     *
     * @param Node  $node  The current AST node being processed.
     * @param Scope $scope The current scope.
     *
     * @return array<RuleError>
     *
     * @throws ShouldNotHappenException
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node instanceof MethodCall) {
            return [];
        }

        if (! $node->name instanceof Identifier || $node->name->toLowerString() !== 'first') {
            return [];
        }

        $callerType = $scope->getType($node->var);

        if ($callerType instanceof UnionType) {
            foreach ($callerType->getTypes() as $type) {
                if ($this->isEloquentModelType($type)) {
                    return [
                        $this->formatErrorMessage(),
                    ];
                }
            }
        } elseif ($this->isEloquentModelType($callerType)) {
            return [
                $this->formatErrorMessage(),
            ];
        }

        return [];
    }

    /**
     * Checks if a given type is an Eloquent Model type.
     * This helps in identifying if the 'first()' method is being called on an actual model instance.
     *
     * @param Type $type The type to check.
     *
     * @throws ShouldNotHappenException
     */
    private function isEloquentModelType(Type $type): bool
    {
        return (new ObjectType(Model::class))->isSuperTypeOf($type)->yes();
    }

    /** @throws ShouldNotHappenException */
    private function formatErrorMessage(): RuleError
    {
        return RuleErrorBuilder::message(
            'Calling \'first()\' on an already fetched Eloquent model instance (e.g., returned by \'find()\' or \'findOrFail()\') is redundant and may cause unexpected behavior because it triggers a new query ignoring the original model context.',
        )
        ->identifier('larastan.noFirstOnSingularModel')
        ->build();
    }
}
