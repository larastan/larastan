<?php

namespace NunoMaduro\Larastan\ReturnTypes\ConsoleCommand;

use InvalidArgumentException;
use NunoMaduro\Larastan\Internal\ConsoleApplicationHelper;
use NunoMaduro\Larastan\Internal\ConsoleApplicationResolver;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function count;
use function in_array;

class OptionDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(private ConsoleApplicationResolver $consoleApplicationResolver, private ConsoleApplicationHelper $consoleApplicationHelper)
    {
    }

    public function getClass(): string
    {
        return 'Illuminate\Console\Command';
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), ['option', 'options'], true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): ?Type {
        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return null;
        }

        $args = $methodCall->getArgs();

        $defaultReturnType = ParametersAcceptorSelector::selectFromArgs($scope, $methodCall->getArgs(), $methodReflection->getVariants())->getReturnType();

        if ($args === [] || $methodReflection->getName() === 'options') {
            return $this->consoleApplicationHelper->getOptions($classReflection, $scope);
        }

        $argStrings = $scope->getType($args[0]->value)->getConstantStrings();

        if (count($argStrings) === 0) {
            return null;
        }

        $returnTypes = [];

        foreach ($argStrings as $argString) {
            $argName = $argString->getValue();

            $argTypes = [];

            foreach ($this->consoleApplicationResolver->findCommands($classReflection) as $command) {
                try {
                    $command->mergeApplicationDefinition();
                    $definition = $command->getDefinition();

                    if ($definition->hasShortcut($argName)) {
                        $argument = $definition->getOptionForShortcut($argName);
                    } else {
                        $argument = $definition->getOption($argName);
                    }

                    $argTypes[] = $this->consoleApplicationHelper->getOptionType($scope, $argument);
                } catch (InvalidArgumentException) {
                    // noop
                }

                $returnTypes[] = count($argTypes) > 0 ? TypeCombinator::union(...$argTypes) : $defaultReturnType;
            }
        }

        return count($returnTypes) > 0 ? TypeCombinator::union(...$returnTypes) : null;
    }
}
