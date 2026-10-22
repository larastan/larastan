<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Contracts\Foundation\Application;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

use function array_map;
use function count;
use function in_array;
use function is_string;
use function ltrim;

/** @implements Rule<MethodCall> */
class OctaneCompatibilityRule implements Rule
{
    private const FORBIDDEN_SERVICES = [
        'request',
        'config',
        'Illuminate\Http\Request',
        'Illuminate\Contracts\Config\Repository',
        'Illuminate\Config\Repository',
    ];

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param MethodCall $node
     *
     * @return RuleError[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Node\Identifier) {
            return [];
        }

        if (! in_array($node->name->name, ['singleton', 'bind'], true)) {
            return [];
        }

        $args = $node->getArgs();

        if (count($args) < 2) {
            return [];
        }

        $calledOnType = $scope->getType($node->var);

        $classNames = $calledOnType->getObjectClassNames();

        if (count($classNames) !== 1) {
            return [];
        }

        if (
            $classNames[0] !== Application::class &&
            ! (new ObjectType(Application::class))->isSuperTypeOf($calledOnType)->yes()
        ) {
            return [];
        }

        // An arrow function is not a subclass of Closure in PHP-Parser, the two
        // are siblings under Expr, so the arrow form of the same binding has to
        // be matched explicitly. Both carry getParams() and getStmts().
        if (
            ! $args[1]->value instanceof Node\Expr\Closure &&
            ! $args[1]->value instanceof Node\Expr\ArrowFunction
        ) {
            return [];
        }

        /** @var Node\Expr\ArrowFunction|Node\Expr\Closure $closure */
        $closure = $args[1]->value;

        /** @var Node\Param[] $closureParams */
        $closureParams = $closure->getParams();

        // Closure should have at least one parameter. First param
        // is container, second is parameters. If no parameter
        // is given we will check for the usage of `$this->app`
        if (count($closureParams) < 1) {
            return $this->checkForThisAppUsage($closure);
        }

        // Using `$app` with `bind` is ok, so we return early
        if ($node->name->name === 'bind') {
            return [];
        }

        if (! $closureParams[0]->var instanceof Node\Expr\Variable) {
            return [];
        }

        if (! is_string($closureParams[0]->var->name)) {
            return [];
        }

        $containerParameterName = $closureParams[0]->var->name;

        $nodes = (new NodeFinder())->find($closure->getStmts(), function (Node $node) use ($containerParameterName, $scope): bool {
            if (! $node instanceof Node\Expr\New_) {
                return false;
            }

            foreach ($node->getArgs() as $arg) {
                if ($this->isForbiddenInjection($arg->value, $containerParameterName, $scope)) {
                    return true;
                }
            }

            return false;
        });

        if (count($nodes) > 0) {
            return array_map([$this, 'dependencyInjectionError'], $nodes);
        }

        return [];
    }

    private function isForbiddenInjection(Node\Expr $expr, string $containerParameterName, Scope $scope): bool
    {
        if ($expr instanceof Node\Expr\Variable && $expr->name === $containerParameterName) {
            return true;
        }

        if ($expr instanceof Node\Expr\ArrayDimFetch) {
            if (! $expr->var instanceof Node\Expr\Variable || $expr->var->name !== $containerParameterName) {
                return false;
            }

            if ($expr->dim === null) {
                return false;
            }

            return $this->isForbiddenServiceKey($expr->dim, $scope);
        }

        if ($expr instanceof Node\Expr\MethodCall) {
            if (! $expr->var instanceof Node\Expr\Variable || $expr->var->name !== $containerParameterName) {
                return false;
            }

            if (! $expr->name instanceof Node\Identifier || ! in_array($expr->name->name, ['make', 'get', 'makeWith'], true)) {
                return false;
            }

            $args = $expr->getArgs();

            if (count($args) < 1) {
                return false;
            }

            return $this->isForbiddenServiceKey($args[0]->value, $scope);
        }

        return false;
    }

    private function isForbiddenServiceKey(Node\Expr $dim, Scope $scope): bool
    {
        foreach ($scope->getType($dim)->getConstantStrings() as $constantString) {
            if (in_array(ltrim($constantString->getValue(), '\\'), self::FORBIDDEN_SERVICES, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Node\Expr\ArrowFunction|Node\Expr\Closure $closure
     *
     * @return RuleError[]
     */
    private function checkForThisAppUsage(Node\Expr $closure): array
    {
        $nodes = (new NodeFinder())->find($closure->getStmts(), static function (Node $node): bool {
            return $node instanceof Node\Expr\PropertyFetch &&
                $node->var instanceof Node\Expr\Variable &&
                $node->var->name === 'this' &&
                $node->name instanceof Node\Identifier &&
                $node->name->name === 'app';
        });

        if (count($nodes) > 0) {
            return array_map([$this, 'dependencyInjectionError'], $nodes);
        }

        return [];
    }

    private function dependencyInjectionError(Node $node): RuleError
    {
        return RuleErrorBuilder::message('Consider using bind method instead or pass a closure.')
            ->identifier('larastan.octaneCompatibility')
            ->tip('See: https://laravel.com/docs/octane#dependency-injection-and-octane')
            ->line($node->getAttribute('startLine'))
            ->build();
    }
}
