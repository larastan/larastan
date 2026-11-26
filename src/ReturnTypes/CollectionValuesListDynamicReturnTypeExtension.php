<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Support\Enumerable;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Infers a list for `all()` called directly on `values()`.
 *
 * A collection type cannot say that its keys are sequential, so the list is
 * only known while `values()` is the receiver of the call. Every enumerable is
 * trusted to follow the contract of `Enumerable::values()` and reset its keys.
 */
#[AutowiredService]
class CollectionValuesListDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Enumerable::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'all';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        $valuesCall = $methodCall->var;

        if (! $valuesCall instanceof MethodCall && ! $valuesCall instanceof NullsafeMethodCall) {
            return null;
        }

        if (! $valuesCall->name instanceof Identifier || $valuesCall->name->toLowerString() !== 'values') {
            return null;
        }

        $valuesCalledOnType = TypeCombinator::removeNull($scope->getType($valuesCall->var));

        if (! (new ObjectType(Enumerable::class))->isSuperTypeOf($valuesCalledOnType)->yes()) {
            return null;
        }

        $returnType = ParametersAcceptorSelector::selectFromArgs($scope, $methodCall->getArgs(), $methodReflection->getVariants())->getReturnType();

        if (! $returnType->isArray()->yes()) {
            return null;
        }

        return TypeCombinator::intersect(
            new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), $returnType->getIterableValueType()),
            new AccessoryArrayListType(),
        );
    }
}
