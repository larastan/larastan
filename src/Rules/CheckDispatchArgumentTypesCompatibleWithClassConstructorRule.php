<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Events\Dispatchable as EventDispatchable;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Type\ClosureType;

use function array_filter;
use function array_values;
use function in_array;

/** @implements Rule<StaticCall> */
class CheckDispatchArgumentTypesCompatibleWithClassConstructorRule implements Rule
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private string $dispatchableClass,
    ) {
    }

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /**
     * @param Scope&NodeCallbackInvoker $scope
     *
     * @return array{}
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->isFirstClassCallable() || ! $node->name instanceof Node\Identifier) {
            return [];
        }

        $methodName = $node->name->name;

        if (! in_array($methodName, $this->getAvailableMethods(), true)) {
            return [];
        }

        if (! $node->class instanceof Node\Name) {
            return [];
        }

        $className = $scope->resolveName($node->class);

        if (! $this->reflectionProvider->hasClass($className)) {
            return [];
        }

        $jobClassReflection = $this->reflectionProvider->getClass($className);

        if (! $jobClassReflection->hasTraitUse($this->dispatchableClass)) {
            return [];
        }

        $args = $node->getArgs();

        if (in_array($methodName, ['dispatchIf', 'dispatchUnless'], true)) {
            $condition = $this->conditionArgument($node, $jobClassReflection, $methodName, $scope);

            if ($condition === null || ! $scope->getType($condition->value)->isBoolean()->yes()) {
                return []; // Handled by other rules
            }

            $args = array_values(array_filter($args, static fn (Arg $arg): bool => $arg !== $condition));
        }

        if ($jobClassReflection->hasConstructor() && ! $jobClassReflection->getConstructor()->isPublic()) {
            // Dispatchable invokes the constructor inside the class, so check its signature without an external visibility check.
            $constructor = $jobClassReflection->getConstructor();
            $variant     = ParametersAcceptorSelector::selectFromArgs($scope, $args, $constructor->getVariants(), $constructor->getNamedArgumentsVariants());
            $callable    = new ClosureType(
                parameters: $variant->getParameters(),
                variadic: $variant->isVariadic(),
                templateTypeMap: $variant->getTemplateTypeMap(),
                resolvedTemplateTypeMap: $variant->getResolvedTemplateTypeMap(),
                acceptsNamedArguments: $constructor->acceptsNamedArguments(),
            );

            $scope->invokeNodeCallback(new FuncCall(new TypeExpr($callable), $args, $node->getAttributes()));
        } else {
            $scope->invokeNodeCallback(new New_($node->class, $args, $node->getAttributes()));
        }

        return [];
    }

    /** The argument that decides whether to dispatch, which is the only one not passed to the constructor. */
    private function conditionArgument(StaticCall $node, ClassReflection $class, string $methodName, Scope $scope): Arg|null
    {
        if (! $class->hasMethod($methodName)) {
            return null;
        }

        $args      = $node->getArgs();
        $method    = $class->getMethod($methodName, $scope);
        $reordered = ArgumentsNormalizer::reorderArgs(
            ParametersAcceptorSelector::selectFromArgs($scope, $args, $method->getVariants(), $method->getNamedArgumentsVariants()),
            $args,
        );
        $condition = $reordered[0] ?? null;

        // The constructor arguments keep their names only in the call as written.
        $original = $condition?->getAttribute(ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE);

        return $original instanceof Arg ? $original : $condition;
    }

    /** @return non-empty-string[] */
    private function getAvailableMethods(): array
    {
        if ($this->dispatchableClass === Dispatchable::class) {
            return [
                'dispatch',
                'dispatchIf',
                'dispatchUnless',
                'dispatchSync',
                'dispatchNow',
                'dispatchAfterResponse',
            ];
        }

        if ($this->dispatchableClass === EventDispatchable::class) {
            return [
                'dispatch',
                'dispatchIf',
                'dispatchUnless',
            ];
        }

        return [];
    }
}
