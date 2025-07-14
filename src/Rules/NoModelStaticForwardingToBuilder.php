<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function array_map;
use function sprintf;

/** @implements Rule<StaticCall> */
final class NoModelStaticForwardingToBuilder implements Rule
{
    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /** @inheritDoc */
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];

        $calledMethods = $node->name instanceof Identifier
            ? [$node->name->toString()]
            : array_map(
                static fn ($name) => $name->getValue(),
                $scope->getType($node->name)->getConstantStrings(),
            );

        $calledOnType = $node->class instanceof Name
            ? $scope->resolveTypeByName($node->class)
            : $scope->getType($node->class);

        foreach ($calledOnType->getObjectClassReflections() as $classReflection) {
            if (! $classReflection->is(Model::class)) {
                continue;
            }

            foreach ($calledMethods as $method) {
                if (! $classReflection->hasMethod($method)) {
                    continue;
                }

                $methodReflection = $classReflection->getMethod($method, $scope);
                $declaringClass   = $methodReflection->getDeclaringClass();

                if (! $declaringClass->is(QueryBuilder::class) && ! $declaringClass->is(EloquentBuilder::class)) {
                    continue;
                }

                $errors[] = RuleErrorBuilder::message(sprintf('Static method [%s] is forwarded to a Builder instance, which is not allowed.', $method))
                    ->tip(sprintf('Use [::query()->%s()] instead.', $method))
                    ->identifier('larastan.noModelStaticForwardingToBuilder')
                    ->line($node->name->getStartLine())
                    ->build();
            }
        }

        return $errors;
    }
}
