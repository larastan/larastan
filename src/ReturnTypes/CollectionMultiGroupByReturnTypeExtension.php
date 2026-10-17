<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function count;
use function in_array;

/**
 * Reconstruct the nested collection structure returned by groupBy([key1, key2, ...]).
 *
 * The generic Collection::groupBy() stub cannot express a depth depending on
 * the number of array elements. Only exact, non-optional constant arrays of
 * supported selectors are analyzed; dynamic inputs retain the framework type.
 *
 * @internal
 */
final class CollectionMultiGroupByReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    private const MAX_GROUPS = 8;

    public function getClass(): string
    {
        return Collection::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'groupBy';
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type|null
    {
        $groupBy      = null;
        $preserveKeys = null;

        foreach ($methodCall->getArgs() as $index => $argument) {
            if ($argument->unpack) {
                return null;
            }

            $name = $argument->name?->name;

            if ($name === 'groupBy' || ($name === null && $index === 0)) {
                $groupBy = $argument->value;
            } elseif ($name === 'preserveKeys' || ($name === null && $index === 1)) {
                $preserveKeys = $argument->value;
            }
        }

        if ($groupBy === null) {
            return null;
        }

        $groupByType    = $scope->getType($groupBy);
        $constantArrays = $groupByType->getConstantArrays();

        if (
            ! $groupByType->isConstantArray()->yes()
            || count($constantArrays) !== 1
            || $constantArrays[0]->getOptionalKeys() !== []
        ) {
            return null;
        }

        $groups = $constantArrays[0]->getValueTypes();
        $depth  = count($groups);

        if ($depth < 2 || $depth > self::MAX_GROUPS) {
            return null;
        }

        // Laravel treats callable arrays like [ClassName::class, 'method'] as
        // a single callback, not two grouping levels. A class-string followed
        // by a method name may be callable even when PHPStan cannot prove it.
        // Only infer two levels when that first selector is definitely NOT
        // a class-string (or when the whole array is definitely not callable).
        if (
            $depth === 2
            && ($groupByType->isCallable()->yes() || ! $groups[0]->isClassString()->no())
        ) {
            return null;
        }

        foreach ($groups as $group) {
            if (! $group->isString()->yes() && ! $group->isCallable()->yes()) {
                return null;
            }
        }

        $receiver = $scope->getType($methodCall->var);

        $receiverClasses = $receiver->getObjectClassNames();

        if (count($receiverClasses) !== 1) {
            return null;
        }

        // A custom collection may override groupBy() with different semantics.
        $collectionClass = $receiverClasses[0];

        if (! in_array($collectionClass, [Collection::class, EloquentCollection::class], true)) {
            return null;
        }

        $keyType   = $receiver->getTemplateType(Collection::class, 'TKey');
        $valueType = $receiver->getTemplateType(Collection::class, 'TValue');

        if ($keyType instanceof ErrorType || $valueType instanceof ErrorType) {
            return null;
        }

        $leafKeyType = new IntegerType();

        if ($preserveKeys !== null) {
            $preserveType = $scope->getType($preserveKeys);

            if (! $preserveType->isBoolean()->yes()) {
                return null;
            }

            if ($preserveType->isTrue()->yes()) {
                $leafKeyType = $keyType;
            } elseif (! $preserveType->isFalse()->yes()) {
                $leafKeyType = TypeCombinator::union(new IntegerType(), $keyType);
            }
        }

        // At runtime Eloquent::groupBy() yields Eloquent leaf collections, but
        // mapping collections instead of models converts outer levels into
        // Support collections. Use the common Support parent for those levels.
        $leafCollection = $collectionClass === EloquentCollection::class
            ? EloquentCollection::class
            : Collection::class;

        $result       = new GenericObjectType($leafCollection, [$leafKeyType, $valueType]);
        $groupKeyType = new BenevolentUnionType([new IntegerType(), new StringType()]);

        for ($level = 0; $level < $depth; $level++) {
            $result = new GenericObjectType(Collection::class, [$groupKeyType, $result]);
        }

        return $result;
    }
}
