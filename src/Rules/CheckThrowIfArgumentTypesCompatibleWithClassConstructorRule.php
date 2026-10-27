<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Throwable;

use function count;
use function in_array;
use function sprintf;
use function str_replace;
use function strtolower;

/**
 * Checks the variadic arguments of throw_if()/throw_unless() against the
 * constructor of the exception class named by the second argument.
 *
 * Laravel instantiates that class with the remaining arguments, but the
 * relationship between the class-string and the variadic arguments cannot be
 * expressed in PHPDoc, so the call is otherwise unchecked.
 *
 * @implements Rule<FuncCall>
 */
class CheckThrowIfArgumentTypesCompatibleWithClassConstructorRule implements Rule
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private FunctionCallParametersCheck $check,
    ) {
    }

    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /** @inheritDoc */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Node\Name) {
            return [];
        }

        $functionName = strtolower($node->name->toString());

        if (! in_array($functionName, ['throw_if', 'throw_unless'], true)) {
            return [];
        }

        $split = $this->splitArgs($node);

        if ($split === null) {
            return [];
        }

        [$conditionArg, $exceptionArg, $constructorArgs] = $split;

        $exceptionType = $scope->getType($exceptionArg->value);

        // A Closure receives the parameters instead of a constructor, and an
        // already instantiated exception ignores them. Neither is checked here.
        if ($exceptionType->isCallable()->yes()) {
            return [];
        }

        $constantStrings = $exceptionType->getConstantStrings();

        if (count($constantStrings) !== 1) {
            return [];
        }

        $className = $constantStrings[0]->getValue();

        if (! $this->reflectionProvider->hasClass($className)) {
            if ($constructorArgs === []) {
                return [];
            }

            return [
                RuleErrorBuilder::message(sprintf(
                    'Function %s() is called with a string that is not a class name, so a RuntimeException is thrown with that string as its message and the given parameters are ignored.',
                    $functionName,
                ))
                    ->identifier('larastan.throwIf.ignoredParameters')
                    ->build(),
            ];
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        if (! $classReflection->is(Throwable::class)) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'Class %s given to %s() does not implement Throwable.',
                    $classReflection->getDisplayName(),
                    $functionName,
                ))
                    ->identifier('larastan.throwIf.notThrowable')
                    ->build(),
            ];
        }

        // An interface extending Throwable, or an abstract exception, cannot be
        // instantiated at all. That is a different defect from the argument
        // types this rule reports on, so it is left to other rules.
        if ($classReflection->isInterface() || $classReflection->isAbstract() || ! $classReflection->hasConstructor()) {
            return [];
        }

        $constructorReflection = $classReflection->getConstructor();
        $classDisplayName      = str_replace('%', '%%', $classReflection->getDisplayName());
        $constructorCall       = (new BuilderFactory())->funcCall($node->name, $constructorArgs);
        $constructorScope      = $this->constructorScope($scope, $conditionArg, $functionName);

        // @phpstan-ignore phpstanApi.method (FunctionCallParametersCheck is how every argument check in PHPStan and Larastan is implemented)
        return $this->check->check(
            ParametersAcceptorSelector::selectFromArgs(
                $constructorScope,
                $constructorArgs,
                $constructorReflection->getVariants(),
                $constructorReflection->getNamedArgumentsVariants(),
            ),
            $constructorScope,
            $constructorReflection->getDeclaringClass()->isBuiltin(),
            $constructorCall,
            'function',
            $constructorReflection->acceptsNamedArguments(),
            'Exception class ' . $classDisplayName . ' constructor invoked with %d parameter in ' . $functionName . '(), %d required.',
            'Exception class ' . $classDisplayName . ' constructor invoked with %d parameters in ' . $functionName . '(), %d required.',
            'Exception class ' . $classDisplayName . ' constructor invoked with %d parameter in ' . $functionName . '(), at least %d required.',
            'Exception class ' . $classDisplayName . ' constructor invoked with %d parameters in ' . $functionName . '(), at least %d required.',
            'Exception class ' . $classDisplayName . ' constructor invoked with %d parameter in ' . $functionName . '(), %d-%d required.',
            'Exception class ' . $classDisplayName . ' constructor invoked with %d parameters in ' . $functionName . '(), %d-%d required.',
            '%s of exception class ' . $classDisplayName . ' constructor expects %s in ' . $functionName . '(), %s given.',
            '', // constructor does not have a return type
            '%s of exception class ' . $classDisplayName . ' constructor is passed by reference, so it expects variables only.',
            'Unable to resolve the template type %s in instantiation of exception class ' . $classDisplayName,
            'Missing parameter $%s in call to ' . $classDisplayName . ' constructor.',
            'Unknown parameter $%s in call to ' . $classDisplayName . ' constructor.',
            'Return type of call to ' . $classDisplayName . ' constructor contains unresolvable type.',
            '%s of exception class ' . $classDisplayName . ' constructor contains unresolvable type.',
            'Exception class ' . $classDisplayName . ' constructor invoked with %s, but it\'s not allowed because of @no-named-arguments.',
            'Constant %s is not allowed for %s of exception class ' . $classDisplayName . ' constructor.',
            'Constants %s cannot be combined for %s of exception class ' . $classDisplayName . ' constructor.',
            'Combining constants with | is not allowed for %s of exception class ' . $classDisplayName . ' constructor.',
            null,
        );
    }

    /**
     * The exception is only constructed when the condition holds, so its
     * arguments are checked in the scope the condition implies.
     *
     * Without this, the common `throw_if($value === null, E::class, $value)`
     * shape would be reported even though the constructor can never receive
     * the excluded type.
     *
     * @param Scope&NodeCallbackInvoker&CollectedDataEmitter $scope
     *
     * @return Scope&NodeCallbackInvoker&CollectedDataEmitter
     */
    private function constructorScope(Scope $scope, Arg|null $conditionArg, string $functionName): Scope
    {
        if ($conditionArg === null) {
            return $scope;
        }

        // Scope::filterByTruthyValue() is declared to return the Scope interface
        // alone, but during analysis it is always the same implementation the
        // rule was handed, which satisfies the intersection FunctionCallParametersCheck asks for.
        // @phpstan-ignore return.type
        return $functionName === 'throw_if'
            ? $scope->filterByTruthyValue($conditionArg->value)
            : $scope->filterByFalseyValue($conditionArg->value);
    }

    /**
     * Separates the exception argument from the parameters forwarded to its
     * constructor, the way Laravel's helper does at runtime.
     *
     * Mirrors PHPStan's own handling of call_user_func(): the leading
     * arguments may be named, and the remaining ones keep their names so they
     * can be checked as named arguments of the constructor.
     *
     * @return array{Arg|null, Arg, list<Arg>}|null
     */
    private function splitArgs(FuncCall $node): array|null
    {
        $conditionArg    = null;
        $exceptionArg    = null;
        $constructorArgs = [];

        foreach ($node->getArgs() as $i => $arg) {
            if ($exceptionArg === null && $arg->unpack) {
                // The positions of $condition and $exception cannot be
                // resolved statically once an argument is unpacked.
                return null;
            }

            if ($arg->name === null ? $i === 0 : $arg->name->name === 'condition') {
                $conditionArg = $arg;

                continue;
            }

            if ($exceptionArg === null) {
                if ($arg->name === null ? $i === 1 : $arg->name->name === 'exception') {
                    $exceptionArg = $arg;

                    continue;
                }
            }

            $constructorArgs[] = $arg;
        }

        if ($exceptionArg === null) {
            return null;
        }

        return [$conditionArg, $exceptionArg, $constructorArgs];
    }
}
