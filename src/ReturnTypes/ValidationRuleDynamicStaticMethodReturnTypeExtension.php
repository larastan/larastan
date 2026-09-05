<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\ArrayRule;
use Illuminate\Validation\Rules\In;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

use function in_array;
use function ucfirst;

/** @internal */
final class ValidationRuleDynamicStaticMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    private const ANY_OF = 'Illuminate\\Validation\\Rules\\AnyOf';

    private const ARRAY_KEYS = 'Illuminate\\Validation\\Rules\\ArrayKeys';

    private const CONDITIONAL_RULES = 'Illuminate\\Validation\\ConditionalRules';

    public function getClass(): string
    {
        return Rule::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), [
            'anyOf',
            'array',
            'arrayKeys',
            'excludeIf',
            'excludeUnless',
            'in',
            'requiredIf',
            'requiredUnless',
            'unless',
            'when',
        ], true);
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
    ): Type {
        $method = $methodReflection->getName();

        return match ($method) {
            'anyOf' => new GenericObjectType(self::ANY_OF, [$this->anyOfArgumentType($methodCall->getArgs(), $scope)]),
            'array' => new GenericObjectType(ArrayRule::class, [$this->arrayArgumentType($methodCall->getArgs(), $scope)]),
            'arrayKeys' => new GenericObjectType(self::ARRAY_KEYS, [$this->arrayArgumentType($methodCall->getArgs(), $scope)]),
            'when', 'unless' => $this->conditionalRulesType($methodCall->getArgs(), $scope, $method === 'unless'),
            'excludeIf', 'excludeUnless', 'requiredIf', 'requiredUnless' => new GenericObjectType(
                'Illuminate\\Validation\\Rules\\' . ucfirst($method),
                [$this->argumentType($methodCall->getArgs(), $scope, 0, 'callback')],
            ),
            default => new GenericObjectType(In::class, [$this->arrayArgumentType($methodCall->getArgs(), $scope)]),
        };
    }

    /** @param array<Arg> $args */
    private function conditionalRulesType(array $args, Scope $scope, bool $unless): Type
    {
        $condition    = $this->argumentType($args, $scope, 0, 'condition');
        $rules        = $this->ruleArgumentType($args, $scope, 1, 'rules');
        $defaultRules = $this->ruleArgumentType($args, $scope, 2, 'defaultRules', true);

        return new GenericObjectType(self::CONDITIONAL_RULES, [
            $condition,
            $unless ? $defaultRules : $rules,
            $unless ? $rules : $defaultRules,
        ]);
    }

    /** @param array<Arg> $args */
    private function argumentType(array $args, Scope $scope, int $index, string $name): Type
    {
        $argument = $this->argument($args, $index, $name);

        return $argument === null || $argument->unpack
            ? new MixedType()
            : $scope->getType($argument->value);
    }

    /** @param array<Arg> $args */
    private function ruleArgumentType(array $args, Scope $scope, int $index, string $name, bool $default = false): Type
    {
        $argument = $this->argument($args, $index, $name);

        if ($argument === null) {
            return $default ? ConstantArrayTypeBuilder::createEmpty()->getArray() : new MixedType();
        }

        $type = $argument->unpack ? new MixedType() : $scope->getType($argument->value);

        return $type->isCallable()->yes()
            ? $type->getCallableParametersAcceptors($scope)[0]->getReturnType()
            : $type;
    }

    /** @param array<Arg> $args */
    private function argument(array $args, int $index, string $name): Arg|null
    {
        foreach ($args as $argument) {
            if ($argument->name?->toString() === $name) {
                return $argument;
            }
        }

        return $args[$index] ?? null;
    }

    /** @param array<Arg> $args */
    private function anyOfArgumentType(array $args, Scope $scope): Type
    {
        if ($args === [] || $args[0]->unpack) {
            return new ArrayType(new MixedType(), new MixedType());
        }

        $type = $scope->getType($args[0]->value);

        return $type->isArray()->yes()
            ? $type
            : new ArrayType(new MixedType(), new MixedType());
    }

    /** @param array<Arg> $args */
    private function arrayArgumentType(array $args, Scope $scope): Type
    {
        foreach ($args as $arg) {
            if ($arg->unpack) {
                return new ArrayType(new IntegerType(), new MixedType());
            }
        }

        if ($args !== []) {
            $type        = $scope->getType($args[0]->value);
            $isArray     = $type->isArray();
            $isArrayable = (new ObjectType(Arrayable::class))->isSuperTypeOf($type);

            if ($isArray->yes()) {
                return $type;
            }

            if ($isArrayable->yes()) {
                $returnType = ParametersAcceptorSelector::selectFromArgs(
                    $scope,
                    [],
                    $type->getMethod('toArray', $scope)->getVariants(),
                )->getReturnType();

                return $returnType->isArray()->yes()
                    ? $returnType
                    : new ArrayType(new MixedType(), new MixedType());
            }

            if (! $isArray->no() || ! $isArrayable->no()) {
                return new ArrayType(new MixedType(), new MixedType());
            }
        }

        $builder = ConstantArrayTypeBuilder::createEmpty();

        foreach ($args as $arg) {
            $builder->setOffsetValueType(null, $scope->getType($arg->value));
        }

        return $builder->getArray();
    }
}
