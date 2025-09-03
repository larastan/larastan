<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function array_map;
use function sprintf;

/** @implements Rule<MethodCall> */
final class NoModelForwardingToBuilder implements Rule
{
    public function getNodeType(): string
    {
        return MethodCall::class;
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

        $calledOnType = $scope->getType($node->var);

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

                $errors[] = RuleErrorBuilder::message(sprintf('Method [%s] is forwarded to a Builder instance, which is not allowed.', $method))
                    ->tip(sprintf('Use [::%s()], [::query()->%s()] or [->newQuery()->%s()] instead.', $method, $method, $method))
                    ->identifier('larastan.noModelForwardingToBuilder')
                    ->line($node->name->getStartLine())
                    ->build();
            }
        }

        return $errors;
    }
}
