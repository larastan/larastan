<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes\ConsoleCommand;

use Larastan\Larastan\Internal\ConsoleApplicationResolver;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;

use function array_unique;
use function count;

class HasOptionDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
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
        return $methodReflection->getName() === 'hasOption';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
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

        $argName = $constantStrings[0]->getValue();

        $returnTypes = [];

        foreach ($this->consoleApplicationResolver->findCommands($classReflection) as $command) {
            $command->mergeApplicationDefinition();
            $returnTypes[] = $command->getDefinition()->hasOption($argName) || $command->getDefinition()->hasShortcut($argName);
        }

        if (count($returnTypes) === 0) {
            return null;
        }

        $returnTypes = array_unique($returnTypes);

        return count($returnTypes) === 1 ? new ConstantBooleanType($returnTypes[0]) : null;
    }
}
