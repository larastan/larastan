<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Validation\Rule;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

use function in_array;

/** Keeps the arguments of the rule objects `Rule` creates in their types, where the form request rules are read from. */
final class ValidationRuleExtension implements DynamicStaticMethodReturnTypeExtension
{
    private const LISTS = ['in', 'array', 'arrayKeys', 'anyOf'];

    private const CONDITIONS = ['requiredIf', 'requiredUnless', 'excludeIf', 'excludeUnless'];

    public function getClass(): string
    {
        return Rule::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array(
            $methodReflection->getName(),
            [...self::LISTS, ...self::CONDITIONS, 'when', 'unless', 'string'],
            true,
        );
    }

    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): Type|null
    {
        $name       = $methodReflection->getName();
        $rule       = ParametersAcceptorSelector::selectFromArgs($scope, $methodCall->getArgs(), $methodReflection->getVariants())->getReturnType();
        $reflection = $rule->getObjectClassReflections()[0] ?? null;
        $arguments  = [];

        if ($reflection === null) {
            return null;
        }

        foreach ($methodCall->getArgs() as $argument) {
            if ($argument->unpack) {
                // The arguments are not known one by one.
                return new GenericObjectType($reflection->getName(), $reflection->typeMapToList($reflection->getTemplateTypeMap()->resolveToBounds()));
            }

            $arguments[] = $scope->getType($argument->value);
        }

        return new GenericObjectType($reflection->getName(), match (true) {
            in_array($name, self::LISTS, true) => [self::list($arguments, $scope)],
            in_array($name, self::CONDITIONS, true) => [$arguments[0]],
            $name === 'when' => [$arguments[0], $arguments[1], $arguments[2] ?? new ConstantArrayType([], [])],
            // The rule class is missing from older Laravel versions, so a stub cannot name it.
            $name === 'string' => [new StringType()],
            default => [$arguments[0], $arguments[2] ?? new ConstantArrayType([], []), $arguments[1]],
        });
    }

    /**
     * The list Laravel's rule objects make of their arguments.
     *
     * @param list<Type> $arguments
     */
    private static function list(array $arguments, Scope $scope): Type
    {
        $first = $arguments[0] ?? null;

        if ($first !== null && (new ObjectType(Arrayable::class))->isSuperTypeOf($first)->yes()) {
            $first = ParametersAcceptorSelector::selectFromArgs($scope, [], $first->getMethod('toArray', $scope)->getVariants())->getReturnType();
        }

        if ($first === null || $first->isArray()->no()) {
            $builder = ConstantArrayTypeBuilder::createEmpty();

            foreach ($arguments as $argument) {
                $builder->setOffsetValueType(null, $argument);
            }

            return $builder->getArray();
        }

        return $first->isArray()->yes() ? $first : new ArrayType(new MixedType(), new MixedType());
    }
}
