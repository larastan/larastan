<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Rules;

use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;

/**
 * This rule checks if the action of a route points to a valid Controller
 * method.
 */
class NoInvalidRouteActionRule implements Rule
{
    /**
     * The method names that can register a route.
     * @var string[]
     */
    protected const REGISTERING_METHODS = [
        'any',
        'delete',
        'get',
        'match',
        'options',
        'patch',
        'post',
        'put',
    ];
    /**
     * @var \PHPStan\Reflection\ReflectionProvider
     */
    protected $reflectionProvider;

    /**
     * NoInvalidRouteActionRule constructor.
     * @param ReflectionProvider $reflectionProvider
     */
    public function __construct(
        ReflectionProvider $reflectionProvider
    ) {
        $this->reflectionProvider = $reflectionProvider;
    }

    /**
     * @return string
     */
    public function getNodeType(): string
    {
        return Node\Expr\StaticCall::class;
    }

    /**
     * @param Node $node
     * @param Scope $scope
     * @return string[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        /** @var \PhpParser\Node\Expr\StaticCall $node */
        if (! $this->isRegisteringRoute($node)) {
            return [];
        }

        if (! ($node->name instanceof Node\Identifier)) {
            return [];
        }

        $method_name = $node->name->toLowerString();

        if (! $this->canAnalyzeArguments($node->args, $method_name)) {
            return [];
        }

        $parsed = $this->parseArgument($node->args[$method_name === 'match' ? 2 : 1]);

        if ($parsed === null) {
            return [];
        }

        [$controller, $method] = $parsed;

        if (! class_exists($controller)) {
            return ["Detected non-existing class '{$controller}' during route registration."];
        }

        if (is_string($method)) {
            $classReflection = $this->reflectionProvider->getClass($controller);
            if (! $classReflection->hasMethod($method)) {
                return ["Detected non-existing method '{$method}' on class '{$controller}' during route registration."];
            }
        }

        return [];
    }

    /**
     * Returns whether this static call is registering a new endpoint.
     * @param Node\Expr\StaticCall $node
     * @return bool
     */
    protected function isRegisteringRoute(Node\Expr\StaticCall $node): bool
    {
        if (! ($node->class instanceof Node\Name)) {
            return false;
        }

        if ($node->class->toString() !== Route::class) {
            return false;
        }

        /** @var Node\Identifier $method_name */
        $method_name = $node->name;

        return in_array($method_name->toLowerString(), self::REGISTERING_METHODS, true);
    }

    /**
     * Returns whether we can properly analyze the argument that was passed
     * to the static method call.
     * @param array<Node\Arg> $arguments
     * @param string $method_name
     * @return bool
     */
    protected function canAnalyzeArguments(array $arguments, string $method_name): bool
    {
        $required_arguments = $method_name === 'match' ? 3 : 2;
        if (count($arguments) < $required_arguments) {
            return false;
        }

        $second_arg = $arguments[$required_arguments - 1];
        return $second_arg->value instanceof Node\Expr\Array_
            || $second_arg->value instanceof Node\Scalar\String_;
    }

    /**
     * Attempts to parse the argument and return both the controller class and method name.
     * Returns null if it was unable to parse the argument.
     * @param Node\Arg $arg
     * @return string[]|null - Null when the arguments could not reliably be parsed.
     */
    protected function parseArgument(Node\Arg $arg): ?array
    {
        $value = $arg->value;
        if ($value instanceof Node\Expr\Array_) {
            if ($this->hasCallableArrayNotation($value)) {
                $controller_class = $this->getControllerClass($value);
                /** @var Node\Scalar\String_ $method */
                $method = $value->items[1]->value;
                return $controller_class !== null ? [
                    $controller_class,
                    $method->value,
                ] : null;
            }

            if (($value = $this->getUsesParam($value)) === null) {
                return null;
            }
        }

        if ($value instanceof Node\Scalar\String_) {
            return $this->getControllerAndMethodFromString($value->value);
        }

        return null;
    }

    /**
     * Returns the value of the 'uses' key in the array, or null if it does not exist.
     * @param Node\Expr\Array_ $value
     * @return Node\Expr|null
     */
    protected function getUsesParam(Node\Expr\Array_ $value): ?Node\Expr
    {
        $uses = Arr::first($value->items, function (Node\Expr\ArrayItem $item): bool {
            if ($item->key === null || ! ($item->key instanceof Node\Scalar\String_)) {
                return false;
            }

            return $item->key->value === 'uses';
        });

        return $uses ? $uses->value : null;
    }

    /**
     * Gets the controller class as a string from the Node or null if we
     * are not able to determine it.
     * @param Node\Expr\Array_ $value
     * @return string|null
     */
    protected function getControllerClass(Node\Expr\Array_ $value): ?string
    {
        $first_arg = $value->items[0]->value;
        if ($first_arg instanceof Node\Expr\ClassConstFetch) {
            if ($first_arg->class instanceof Node\Name) {
                return $first_arg->class->toCodeString();
            }
        }

        if ($first_arg instanceof Node\Scalar\String_) {
            return $first_arg->value;
        }

        return null;
    }

    /**
     * Given a string ith a value of 'App\SomeController@index',
     * will return ['App\SomeController', 'index']. In the case of an invokable controller:
     * 'App\SomeOtherController', it will return ['App\SomeController', '__invoke'].
     * @param string $action
     * @return string[]
     */
    protected function getControllerAndMethodFromString(string $action): array
    {
        if (Str::contains($action, '@')) {
            return [
                Str::before($action, '@'),
                Str::after($action, '@'),
            ];
        }

        return [$action, '__invoke'];
    }

    /**
     * Returns whether the route is being registered using the [Controller::class, 'index'] method.
     * @param Node\Expr\Array_ $array
     * @return bool
     */
    protected function hasCallableArrayNotation(Node\Expr\Array_ $array): bool
    {
        if (count($array->items) < 2) {
            return false;
        }

        if (! ($array->items[1]->value instanceof Node\Scalar\String_)) {
            return false;
        }

        if ($array->items[1]->key !== null) {
            // Ensure we don't have an associative array
            return false;
        }

        return $array->items[0]->value instanceof Node\Scalar\String_
            || $array->items[0]->value instanceof Node\Expr\ClassConstFetch;
    }
}
