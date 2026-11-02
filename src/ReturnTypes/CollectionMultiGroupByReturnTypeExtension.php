<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function array_values;
use function count;

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
        $args = $methodCall->getArgs();

        if ($args === [] || $args[0]->unpack || (isset($args[1]) && $args[1]->unpack)) {
            return null;
        }

        $groupByType    = $scope->getType($args[0]->value);
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

        if ($depth === 0) {
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

        // An override can change the grouping semantics.
        if ($methodReflection->getDeclaringClass()->getName() !== Collection::class) {
            return null;
        }

        $collectionClass = $receiverClasses[0];
        $reflections     = $receiver->getObjectClassReflections();

        // Rebuilding a subclass requires its usual key/value template pair.
        if (count($reflections) !== 1 || count($reflections[0]->getTemplateTypeMap()->getTypes()) !== 2) {
            return null;
        }

        $templates = array_values($reflections[0]->getTemplateTypeMap()->getTypes());
        $declared  = new GenericObjectType($collectionClass, $templates);

        // Two parameters alone are not enough: subclasses may reverse their
        // order or fix an ancestor parameter instead of forwarding it.
        if (
            ! $declared->getTemplateType(Collection::class, 'TKey')->equals($templates[0])
            || ! $declared->getTemplateType(Collection::class, 'TValue')->equals($templates[1])
        ) {
            return null;
        }

        $keyType   = $receiver->getTemplateType(Collection::class, 'TKey');
        $valueType = $receiver->getTemplateType(Collection::class, 'TValue');

        if ($keyType instanceof ErrorType || $valueType instanceof ErrorType) {
            return null;
        }

        $preserveType = (isset($args[1]) ? $scope->getType($args[1]->value) : new ConstantBooleanType(false))->toBoolean();
        $leafKeyType  = new IntegerType();

        if ($preserveType->isTrue()->yes()) {
            $leafKeyType = $keyType;
        } elseif (! $preserveType->isFalse()->yes()) {
            $leafKeyType = TypeCombinator::union(new IntegerType(), $keyType);
        }

        $groupKeyType = new BenevolentUnionType([new IntegerType(), new StringType()]);
        $result       = new GenericObjectType($collectionClass, [$leafKeyType, $valueType]);
        $result       = new GenericObjectType($collectionClass, [$groupKeyType, $result]);

        // Eloquent::map() converts collections of non-model values to Support.
        // The final groupBy() does not map, so the two innermost levels retain
        // the receiver's class, including custom collection subclasses.
        $outerClass = $reflections[0]->isSubclassOf(EloquentCollection::class) || $collectionClass === EloquentCollection::class
            ? Collection::class
            : $collectionClass;

        for ($level = 1; $level < $depth; $level++) {
            $result = new GenericObjectType($outerClass, [$groupKeyType, $result]);
        }

        return $result;
    }
}
