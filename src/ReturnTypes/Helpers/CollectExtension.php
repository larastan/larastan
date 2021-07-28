<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes\Helpers;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Iterator;
use IteratorAggregate;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\TypeWithClassName;
use Traversable;

final class CollectExtension implements DynamicFunctionReturnTypeExtension
{
    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $functionReflection->getName() === 'collect';
    }

    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope
    ): Type {
        if (count($functionCall->args) < 1) {
            return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
        }

        $keyType = TypeCombinator::union(new IntegerType(), new StringType());

        $valueType = $scope->getType($functionCall->args[0]->value);

        if ($valueType instanceof TypeWithClassName) {
            if ((new ObjectType(Enumerable::class))->isSuperTypeOf($valueType)->yes()) {
                return $this->getTypeFromEloquentCollection($valueType);
            }

            if (
                (new ObjectType(Traversable::class))->isSuperTypeOf($valueType)->yes() ||
                (new ObjectType(IteratorAggregate::class))->isSuperTypeOf($valueType)->yes() ||
                (new ObjectType(Iterator::class))->isSuperTypeOf($valueType)->yes()
            ) {
                return $this->getTypeFromIterator($valueType);
            }
        }

        if (! $valueType->isArray()->yes()) {
            return new GenericObjectType(Collection::class, [$valueType->toArray()->getIterableKeyType(), $valueType->toArray()->getIterableValueType()]);
        }

        if ($valueType->isIterableAtLeastOnce()->no()) {
            return new GenericObjectType(Collection::class, [$keyType, new MixedType()]);
        }

        return new GenericObjectType(Collection::class, [
            TypeUtils::generalizeType($valueType->getIterableKeyType()),
            TypeUtils::generalizeType($valueType->getIterableValueType()),
        ]);
    }

    private function getTypeFromEloquentCollection(TypeWithClassName $valueType): GenericObjectType
    {
        $keyType = TypeCombinator::union(new IntegerType(), new StringType());

        $classReflection = $valueType->getClassReflection();

        if ($classReflection === null) {
            return new GenericObjectType(Collection::class, [$keyType, new MixedType()]);
        }

        $innerValueType = $classReflection->getActiveTemplateTypeMap()->getType('TValue');

        if ($classReflection->getName() === EloquentCollection::class || $classReflection->isSubclassOf(EloquentCollection::class)) {
            $keyType = new IntegerType();
        }

        if ($innerValueType !== null) {
            return new GenericObjectType(Collection::class, [$keyType, $innerValueType]);
        }

        return new GenericObjectType(Collection::class, [$keyType, new MixedType()]);
    }

    private function getTypeFromIterator(TypeWithClassName $valueType): GenericObjectType
    {
        $keyType = TypeCombinator::union(new IntegerType(), new StringType());

        $classReflection = $valueType->getClassReflection();

        if ($classReflection === null) {
            return new GenericObjectType(Collection::class, [$keyType, new MixedType()]);
        }

        $templateTypes = array_values($classReflection->getActiveTemplateTypeMap()->getTypes());

        if (count($templateTypes) === 1) {
            return new GenericObjectType(Collection::class, [$keyType, $templateTypes[0]]);
        }

        return new GenericObjectType(Collection::class, $templateTypes);
    }
}
