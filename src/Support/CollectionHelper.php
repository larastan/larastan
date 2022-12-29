<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Support;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Iterator;
use IteratorAggregate;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;
use Traversable;

final class CollectionHelper
{
    public function determineGenericCollectionTypeFromType(Type $type): ?GenericObjectType
    {
        if ($type instanceof TypeWithClassName) {
            if ((new ObjectType(Enumerable::class))->isSuperTypeOf($type)->yes()) {
                return $this->getTypeFromEloquentCollection($type);
            }

            if (
                (new ObjectType(Traversable::class))->isSuperTypeOf($type)->yes() ||
                (new ObjectType(IteratorAggregate::class))->isSuperTypeOf($type)->yes() ||
                (new ObjectType(Iterator::class))->isSuperTypeOf($type)->yes()
            ) {
                return $this->getTypeFromIterator($type);
            }
        }

        if (! $type->isArray()->yes()) {
            return new GenericObjectType(Collection::class, [$type->toArray()->getIterableKeyType(), $type->toArray()->getIterableValueType()]);
        }

        if ($type->isIterableAtLeastOnce()->no()) {
            return new GenericObjectType(Collection::class, [new BenevolentUnionType([new IntegerType(), new StringType()]), new MixedType()]);
        }

        return null;
    }

    private function getTypeFromEloquentCollection(TypeWithClassName $valueType): ?GenericObjectType
    {
        $keyType = new BenevolentUnionType([new IntegerType(), new StringType()]);

        $classReflection = $valueType->getClassReflection();

        if ($classReflection === null) {
            return null;
        }

        $innerValueType = $classReflection->getActiveTemplateTypeMap()->getType('TModel');

        if ($classReflection->getName() === EloquentCollection::class || $classReflection->isSubclassOf(EloquentCollection::class)) {
            $keyType = new IntegerType();
        }

        if ($innerValueType !== null) {
            return new GenericObjectType(Collection::class, [$keyType, $innerValueType]);
        }

        return null;
    }

    private function getTypeFromIterator(TypeWithClassName $valueType): ?GenericObjectType
    {
        $keyType = new BenevolentUnionType([new IntegerType(), new StringType()]);

        $classReflection = $valueType->getClassReflection();

        if ($classReflection === null) {
            return null;
        }

        $templateTypes = array_values($classReflection->getActiveTemplateTypeMap()->getTypes());

        if (count($templateTypes) === 1) {
            return new GenericObjectType(Collection::class, [$keyType, $templateTypes[0]]);
        }

        return new GenericObjectType(Collection::class, $templateTypes);
    }
}
