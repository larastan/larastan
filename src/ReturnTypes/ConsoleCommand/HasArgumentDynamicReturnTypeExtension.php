<?php

namespace NunoMaduro\Larastan\ReturnTypes\ConsoleCommand;

use NunoMaduro\Larastan\Internal\ConsoleApplicationResolver;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;

class HasArgumentDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(private ConsoleApplicationResolver $consoleApplicationResolver)
    {
    }

    public function getClass(): string
    {
        return 'Illuminate\Console\Command';
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'hasArgument';
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

        if ($methodCall->getArgs() === []) {
            return null;
        }

        $constantStrings = $scope->getType($methodCall->getArgs()[0]->value)->getConstantStrings();

        if (count($constantStrings) !== 1) {
            return null;
        }

        $returnTypes = [];

        foreach ($this->consoleApplicationResolver->findCommands($classReflection) as $command) {
            $command->mergeApplicationDefinition();
            $returnTypes[] = $command->getDefinition()->hasArgument($constantStrings[0]->getValue());
        }

        if (count($returnTypes) === 0) {
            return null;
        }

        $returnTypes = array_unique($returnTypes);

        return count($returnTypes) === 1 ? new ConstantBooleanType($returnTypes[0]) : null;
    }
}
