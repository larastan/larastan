<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Type;

use function array_values;

/**
 * Orders the arguments of a call like the parameters they are bound to.
 *
 * Rules and collectors receive calls as written, where a named argument can be at any position.
 *
 * @internal
 */
#[AutowiredService]
final class CallArgumentsHelper
{
    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    /** @return list<Arg>|null Null when the named arguments cannot be matched to the function's parameters. */
    public function ofFunctionCall(FuncCall $call, Scope $scope): array|null
    {
        $args = $call->getArgs();

        if (! self::hasNamedArgument($args)) {
            return array_values($args);
        }

        if (! $call->name instanceof Name || ! $this->reflectionProvider->hasFunction($call->name, $scope)) {
            return null;
        }

        return self::reorder($args, $this->reflectionProvider->getFunction($call->name, $scope), $scope);
    }

    /**
     * @param Type|null $calledOn The type declaring the method, when it is not the type the call is written on.
     *
     * @return list<Arg>|null Null when the named arguments cannot be matched to the method's parameters.
     */
    public function ofMethodCall(MethodCall|StaticCall $call, Scope $scope, Type|null $calledOn = null): array|null
    {
        $args = $call->getArgs();

        if (! self::hasNamedArgument($args)) {
            return array_values($args);
        }

        if (! $call->name instanceof Identifier) {
            return null;
        }

        if ($calledOn === null && $call instanceof StaticCall) {
            $calledOn = $call->class instanceof Name ? $scope->resolveTypeByName($call->class) : $scope->getType($call->class);
        }

        $calledOn ??= $scope->getType($call->var);
        $method     = $call->name->toString();

        if (! $calledOn->hasMethod($method)->yes()) {
            return null;
        }

        return self::reorder($args, $calledOn->getMethod($method, $scope), $scope);
    }

    /** @return list<Arg>|null Null when the named arguments cannot be matched to the constructor's parameters. */
    public function ofNew(New_ $call, Scope $scope): array|null
    {
        $args = $call->getArgs();

        if (! self::hasNamedArgument($args)) {
            return array_values($args);
        }

        if (! $call->class instanceof Name || ! $this->reflectionProvider->hasClass($scope->resolveName($call->class))) {
            return null;
        }

        $class = $this->reflectionProvider->getClass($scope->resolveName($call->class));

        return $class->hasConstructor() ? self::reorder($args, $class->getConstructor(), $scope) : null;
    }

    /** @param Arg[] $args */
    private static function hasNamedArgument(array $args): bool
    {
        foreach ($args as $arg) {
            if ($arg->name !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Arg[] $args
     *
     * @return list<Arg>|null
     */
    private static function reorder(array $args, FunctionReflection|ExtendedMethodReflection $reflection, Scope $scope): array|null
    {
        return ArgumentsNormalizer::reorderArgs(
            ParametersAcceptorSelector::selectFromArgs($scope, $args, $reflection->getVariants(), $reflection->getNamedArgumentsVariants()),
            $args,
        );
    }
}
