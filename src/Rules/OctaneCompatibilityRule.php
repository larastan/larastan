<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Rules;

use Illuminate\Contracts\Foundation\Application;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * @implements Rule<MethodCall>
 */
class OctaneCompatibilityRule implements Rule
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /** @param MethodCall $node */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Node\Identifier) {
            return [];
        }

        if (! in_array($node->name->name, ['singleton', 'bind'], true)) {
            return [];
        }

        $args = $node->args;

        if (count($args) < 2) {
            return [];
        }

        $calledOnType = $scope->getType($node->var);

        if (! $calledOnType instanceof ObjectType) {
            return [];
        }

        if ($calledOnType->getClassName() !== Application::class &&
            ! (new ObjectType(Application::class))->isSuperTypeOf($calledOnType)->yes()
        ) {
            return [];
        }

        if (! $args[1]->value instanceof Node\Expr\Closure) {
            return [];
        }

        /** @var Node\Expr\Closure $closure */
        $closure = $args[1]->value;

        /** @var Node\Param[] $closureParams */
        $closureParams = $closure->getParams();

        // Closure should have at least one parameter. First param
        // is container, second is parameters. If no parameter
        // is given we will check for the usage of `$this->app`
        if (count($closureParams) < 1) {
            return $this->checkForThisAppUsage($scope, $closure);
        }

        // Using `$app` with `bind` is ok, so we return early
        if ($node->name->name === 'bind') {
            return [];
        }

        if (! $closureParams[0]->var instanceof Node\Expr\Variable) {
            return [];
        }

        $containerParameterName = $closureParams[0]->var->name;

        $nodes = (new NodeFinder)->find($closure->getStmts(), function (Node $node) use ($containerParameterName): bool {
            if (! $node instanceof Node\Expr\New_) {
                return false;
            }

            if (count($node->args) < 1) {
                return false;
            }

            if (! $node->args[0]->value instanceof Node\Expr\Variable && ! $node->args[0]->value instanceof Node\Expr\ArrayDimFetch) {
                return false;
            }

            if ($node->args[0]->value instanceof Node\Expr\ArrayDimFetch) {
                /** @var Node\Expr\Variable $var */
                $var = $node->args[0]->value->var;

                if ($var->name !== $containerParameterName) {
                    return false;
                }

                if ($node->args[0]->value->dim === null) {
                    return false;
                }

                if (! $node->args[0]->value->dim instanceof Node\Scalar\String_) {
                    return false;
                }

                return in_array($node->args[0]->value->dim->value, ['request', 'config'], true);
            }

            if ($node->args[0]->value->name !== $containerParameterName) {
                return false;
            }

            return true;
        });

        if (count($nodes) > 0) {
            $errors = [];

            foreach ($nodes as $node) {
                $errors[] = RuleErrorBuilder::message('Consider using bind method instead or pass a closure.')
                    ->identifier('rules.octane')->tip('See: https://github.com/laravel/octane#dependency-injection--octane')->line($node->getAttribute('startLine'))->build();
            }

            return $errors;
        }

        return [];
    }

    /** @return \PHPStan\Rules\RuleError[] */
    private function checkForThisAppUsage(Scope $scope, Node\Expr\Closure $closure): array
    {
        $nodes = (new NodeFinder)->find($closure->getStmts(), function (Node $node): bool {
            return $node instanceof Node\Expr\PropertyFetch &&
                $node->var instanceof Node\Expr\Variable &&
                $node->var->name === 'this' &&
                $node->name instanceof Node\Identifier &&
                $node->name->name === 'app';
        });

        if (count($nodes) > 0) {
            $errors = [];

            foreach ($nodes as $node) {
                $errors[] = RuleErrorBuilder::message('Consider using bind method instead or pass a closure.')
                    ->identifier('rules.octane')->tip('See: https://github.com/laravel/octane#dependency-injection--octane')->line($node->getAttribute('startLine'))->build();
            }

            return $errors;
        }

        return [];
    }
}
