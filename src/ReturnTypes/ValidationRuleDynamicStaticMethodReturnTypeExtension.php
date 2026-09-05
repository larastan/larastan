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

/** @internal */
final class ValidationRuleDynamicStaticMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    private const ANY_OF = 'Illuminate\\Validation\\Rules\\AnyOf';

    private const ARRAY_KEYS = 'Illuminate\\Validation\\Rules\\ArrayKeys';

    public function getClass(): string
    {
        return Rule::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), ['anyOf', 'array', 'arrayKeys', 'in'], true);
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
    ): Type {
        return match ($methodReflection->getName()) {
            'anyOf' => new GenericObjectType(self::ANY_OF, [$this->anyOfArgumentType($methodCall->getArgs(), $scope)]),
            'array' => new GenericObjectType(ArrayRule::class, [$this->arrayArgumentType($methodCall->getArgs(), $scope)]),
            'arrayKeys' => new GenericObjectType(self::ARRAY_KEYS, [$this->arrayArgumentType($methodCall->getArgs(), $scope)]),
            default => new GenericObjectType(In::class, [$this->arrayArgumentType($methodCall->getArgs(), $scope)]),
        };
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
