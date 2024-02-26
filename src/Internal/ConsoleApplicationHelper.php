<?php

declare(strict_types=1);

namespace Larastan\Larastan\Internal;

use InvalidArgumentException;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function count;

final class ConsoleApplicationHelper
{
    public function __construct(private ConsoleApplicationResolver $consoleApplicationResolver)
    {
    }

    public function getArguments(ClassReflection $classReflection, Scope $scope): Type|null
    {
        $argTypes = [];

        foreach ($this->consoleApplicationResolver->findCommands($classReflection) as $command) {
            try {
                $arguments = $command->getDefinition()->getArguments();
                $builder   = ConstantArrayTypeBuilder::createEmpty();

                foreach ($arguments as $name => $argument) {
                    $argumentType = $this->getArgumentType($scope, $argument);
                    $builder->setOffsetValueType(new ConstantStringType($name), $argumentType);
                }

                $argTypes[] = $builder->getArray();
            } catch (InvalidArgumentException) {
                // noop
            }
        }

        return count($argTypes) > 0 ? TypeCombinator::union(...$argTypes) : null;
    }

    public function getArgumentType(Scope $scope, InputArgument $argument): Type
    {
        if ($argument->isArray()) {
            $argType = new ArrayType(new IntegerType(), new StringType());

            if (! $argument->isRequired() && $argument->getDefault() !== []) {
                $argType = TypeCombinator::union($argType, $scope->getTypeFromValue($argument->getDefault()));
            }
        } else {
            $argType = new StringType();

            if (! $argument->isRequired()) {
                $argType = TypeCombinator::union($argType, $scope->getTypeFromValue($argument->getDefault()));
            }
        }

        return $argType;
    }

    public function getOptions(ClassReflection $classReflection, Scope $scope): Type|null
    {
        $optTypes = [];

        foreach ($this->consoleApplicationResolver->findCommands($classReflection) as $command) {
            try {
                $options = $command->getDefinition()->getOptions();
                $builder = ConstantArrayTypeBuilder::createEmpty();
                foreach ($options as $name => $option) {
                    $argumentType = $this->getOptionType($scope, $option);
                    $builder->setOffsetValueType(new ConstantStringType($name), $argumentType);
                }

                $optTypes[] = $builder->getArray();
            } catch (InvalidArgumentException) {
                // noop
            }
        }

        return count($optTypes) > 0 ? TypeCombinator::union(...$optTypes) : null;
    }

    public function getOptionType(Scope $scope, InputOption $option): Type
    {
        if (! $option->acceptValue()) {
            if ($option->isNegatable()) {
                return new UnionType([new BooleanType(), new NullType()]);
            }

            return new BooleanType();
        }

        $optType = TypeCombinator::union(new StringType(), new NullType());

        if ($option->isValueRequired() && ($option->isArray() || $option->getDefault() !== null)) {
            $optType = TypeCombinator::removeNull($optType);
        }

        if ($option->isArray()) {
            $optType = new ArrayType(new IntegerType(), $optType);
        }

        return TypeCombinator::union($optType, $scope->getTypeFromValue($option->getDefault()));
    }
}
