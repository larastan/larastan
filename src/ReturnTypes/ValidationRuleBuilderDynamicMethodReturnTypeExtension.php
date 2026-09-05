<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Validation\Rules\Date;
use Illuminate\Validation\Rules\Numeric;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function class_exists;
use function count;
use function in_array;
use function is_int;

/** @internal */
final class ValidationRuleBuilderDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /** @param class-string $className */
    public function __construct(private string $className)
    {
    }

    public function getClass(): string
    {
        return class_exists($this->className) ? $this->className : self::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array(
            $methodReflection->getName(),
            match ($this->className) {
                Date::class => ['format'],
                Numeric::class => ['between', 'exactly', 'max', 'min'],
                default => ['between', 'exactly', 'lowercase', 'min', 'uppercase'],
            },
            true,
        );
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type {
        $valueType = TypeCombinator::intersect(
            $scope->getType($methodCall->var)->getTemplateType($this->className, 'TValue'),
            ParametersAcceptorSelector::selectFromArgs(
                $scope,
                $methodCall->getArgs(),
                $methodReflection->getVariants(),
            )->getReturnType()->getTemplateType($this->className, 'TValue'),
        );

        $valueType = match ($this->className) {
            Date::class => TypeCombinator::intersect(
                $valueType,
                TypeCombinator::union(new FloatType(), new IntegerType(), new StringType()),
            ),
            Numeric::class => $this->refineInteger($valueType, $methodReflection->getName(), $methodCall, $scope),
            default => $this->refineString($valueType, $methodReflection->getName(), $methodCall, $scope),
        };

        return new GenericObjectType($this->className, [$valueType]);
    }

    private function refineInteger(Type $type, string $method, MethodCall $call, Scope $scope): Type
    {
        if (! (new IntegerType())->isSuperTypeOf($type)->yes()) {
            return $type;
        }

        $first = $this->constantIntegerArgument($call, $scope, 0);

        if ($first === null) {
            return $type;
        }

        $minimum = in_array($method, ['between', 'exactly', 'min'], true) ? $first : null;
        $maximum = in_array($method, ['exactly', 'max'], true)
            ? $first
            : ($method === 'between' ? $this->constantIntegerArgument($call, $scope, 1) : null);

        if ($method === 'between' && $maximum === null) {
            return $type;
        }

        return TypeCombinator::intersect($type, IntegerRangeType::fromInterval($minimum, $maximum));
    }

    private function refineString(Type $type, string $method, MethodCall $call, Scope $scope): Type
    {
        if ($method === 'lowercase') {
            return TypeCombinator::intersect($type, new AccessoryLowercaseStringType());
        }

        if ($method === 'uppercase') {
            return TypeCombinator::intersect($type, new AccessoryUppercaseStringType());
        }

        $minimum = $this->constantIntegerArgument($call, $scope, 0);

        return $minimum !== null && $minimum > 0
            ? TypeCombinator::intersect($type, new AccessoryNonEmptyStringType())
            : $type;
    }

    private function constantIntegerArgument(MethodCall $call, Scope $scope, int $index): int|null
    {
        if (! isset($call->getArgs()[$index])) {
            return null;
        }

        $values = $scope->getType($call->getArgs()[$index]->value)->getConstantScalarValues();

        return count($values) === 1 && is_int($values[0]) ? $values[0] : null;
    }
}
