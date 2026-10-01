<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Validation\Rules\Numeric;
use Larastan\Larastan\Support\Validation\RuleTypes;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;

use function array_map;
use function array_values;

/** Narrows the value a fluent numeric rule accepts as constraints are added to it. */
final class NumericRuleExtension implements DynamicMethodReturnTypeExtension
{
    /** Methods and the size rule they add. */
    private const BOUNDS = ['min' => 'Min', 'max' => 'Max', 'between' => 'Between', 'exactly' => 'Size'];

    public function getClass(): string
    {
        return Numeric::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getDeclaringClass()->getName() === Numeric::class && $methodReflection->getName() !== '__toString';
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $name      = $methodReflection->getName();
        $value     = $scope->getType($methodCall->var)->getTemplateType(Numeric::class, 'TValue');
        $arguments = array_values(array_map(static fn (Arg $argument): Type => $scope->getType($argument->value), $methodCall->getArgs()));

        if ($name === 'integer' && ($arguments[0] ?? null)?->isTrue()->yes() === true) {
            $value = new IntegerType();
        }

        if (isset(self::BOUNDS[$name])) {
            $parameters = array_map(static fn (Type $argument): string => (string) ($argument->getConstantScalarValues()[0] ?? ''), $arguments);
            $value      = RuleTypes::bound($value, [self::BOUNDS[$name] => [$parameters]]);
        }

        return new GenericObjectType(Numeric::class, [$value]);
    }
}
