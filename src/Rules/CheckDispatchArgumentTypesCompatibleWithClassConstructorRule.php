<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Events\Dispatchable as EventDispatchable;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\BooleanType;

use function array_shift;
use function count;
use function in_array;
use function sprintf;
use function str_replace;
use function ucfirst;

/** @implements Rule<StaticCall> */
class CheckDispatchArgumentTypesCompatibleWithClassConstructorRule implements Rule
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private FunctionCallParametersCheck $check,
        private string $dispatchableClass,
    ) {
    }

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /** @inheritDoc */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Node\Identifier) {
            return [];
        }

        $methodName = $node->name->name;

        if (! in_array($methodName, $this->getAvailableMethods(), true)) {
            return [];
        }

        if (! $node->class instanceof Node\Name) {
            return [];
        }

        $jobClassReflection = $this->reflectionProvider->getClass($scope->resolveName($node->class));

        if (! $jobClassReflection->hasTraitUse($this->dispatchableClass)) {
            return [];
        }

        $jobOrEvent = $this->dispatchableClass === Dispatchable::class ? 'job' : 'event';

        if (! $jobClassReflection->hasConstructor()) {
            $requiredArgCount = 0;

            if (in_array($methodName, ['dispatchIf', 'dispatchUnless'], true)) {
                $requiredArgCount = 1;
            }

            if (count($node->getArgs()) > $requiredArgCount) {
                return [
                    RuleErrorBuilder::message(sprintf(
                        ucfirst($jobOrEvent) . ' class %s does not have a constructor and must be dispatched without any parameters.',
                        $jobClassReflection->getDisplayName(),
                    ))
                        ->identifier(sprintf('larastan.%s.noConstructor', $jobOrEvent . 's'))
                        ->build(),
                ];
            }

            return [];
        }

        if (in_array($methodName, ['dispatchIf', 'dispatchUnless'], true)) {
            if ($node->getArgs() === []) {
                return []; // Handled by other rules
            }

            $firstArgType = $scope->getType($node->getArgs()[0]->value);
            if (! (new BooleanType())->isSuperTypeOf($firstArgType)->yes()) {
                return []; // Handled by other rules
            }
        }

        $constructorReflection = $jobClassReflection->getConstructor();

        $classDisplayName = str_replace('%', '%%', $jobClassReflection->getDisplayName());

        // Special case because these methods have another parameter as first argument.
        if (in_array($methodName, ['dispatchIf', 'dispatchUnless'], true)) {
            $args = $node->getArgs();
            array_shift($args);
            $node = (new BuilderFactory())->staticCall($node->class, $node->name, $args);
        }

        // @phpstan-ignore-next-line
        return $this->check->check(
            ParametersAcceptorSelector::selectFromArgs(
                $scope,
                $node->getArgs(),
                $constructorReflection->getVariants(),
            ),
            $scope,
            $constructorReflection->getDeclaringClass()->isBuiltin(),
            $node,
            [
                ucfirst($jobOrEvent) . ' class ' . $classDisplayName . ' constructor invoked with %d parameter in ' . $classDisplayName . '::' . $methodName . '(), %d required.',
                ucfirst($jobOrEvent) . ' class ' . $classDisplayName . ' constructor invoked with %d parameters in ' . $classDisplayName . '::' . $methodName . '(), %d required.',
                ucfirst($jobOrEvent) . ' class ' . $classDisplayName . ' constructor invoked with %d parameter in ' . $classDisplayName . '::' . $methodName . '(), at least %d required.',
                ucfirst($jobOrEvent) . ' class ' . $classDisplayName . ' constructor invoked with %d parameters in ' . $classDisplayName . '::' . $methodName . '(), at least %d required.',
                ucfirst($jobOrEvent) . ' class ' . $classDisplayName . ' constructor invoked with %d parameter in ' . $classDisplayName . '::' . $methodName . '(), %d-%d required.',
                ucfirst($jobOrEvent) . ' class ' . $classDisplayName . ' constructor invoked with %d parameters in ' . $classDisplayName . '::' . $methodName . '(), %d-%d required.',
                'Parameter %s of ' . $jobOrEvent . ' class ' . $classDisplayName . ' constructor expects %s in ' . $classDisplayName . '::' . $methodName . '(), %s given.',
                '', // constructor does not have a return type
                'Parameter %s of ' . $jobOrEvent . ' class ' . $classDisplayName . ' constructor is passed by reference, so it expects variables only',
                'Unable to resolve the template type %s in instantiation of ' . $jobOrEvent . ' class ' . $classDisplayName,
                'Missing parameter $%s in call to ' . $classDisplayName . ' constructor.',
                'Unknown parameter $%s in call to ' . $classDisplayName . ' constructor.',
                'Return type of call to ' . $classDisplayName . ' constructor contains unresolvable type.',
            ],
        );
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
