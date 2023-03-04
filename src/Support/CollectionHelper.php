<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Support;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Iterator;
use IteratorAggregate;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use Traversable;

final class CollectionHelper
{
    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    public function determineGenericCollectionTypeFromType(Type $type): ?GenericObjectType
    {
        $classReflections = $type->getObjectClassReflections();

        if (count($classReflections) > 0) {
            if ((new ObjectType(Enumerable::class))->isSuperTypeOf($type)->yes()) {
                return $this->getTypeFromEloquentCollection($classReflections[0]);
            }

            if (
                (new ObjectType(Traversable::class))->isSuperTypeOf($type)->yes() ||
                (new ObjectType(IteratorAggregate::class))->isSuperTypeOf($type)->yes() ||
                (new ObjectType(Iterator::class))->isSuperTypeOf($type)->yes()
            ) {
                return $this->getTypeFromIterator($classReflections[0]);
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

    public function determineCollectionClassName(string $modelClassName): string
    {
        try {
            $newCollectionMethod = $this->reflectionProvider->getClass($modelClassName)->getNativeMethod('newCollection');
            $returnType = ParametersAcceptorSelector::selectSingle($newCollectionMethod->getVariants())->getReturnType();

            $classNames = $returnType->getObjectClassNames();

            if (count($classNames) === 1) {
                return $classNames[0];
            }

            return $returnType->describe(VerbosityLevel::value());
        } catch (MissingMethodFromReflectionException|ShouldNotHappenException) {
            return EloquentCollection::class;
        }
    }

    public function determineCollectionClass(string $modelClassName): Type
    {
        $collectionClassName = $this->determineCollectionClassName($modelClassName);
        $collectionReflection = $this->reflectionProvider->getClass($collectionClassName);

        if ($collectionReflection->isGeneric()) {
            $typeMap = $collectionReflection->getActiveTemplateTypeMap();

            // Specifies key and value
            if ($typeMap->count() === 2) {
                return new GenericObjectType($collectionClassName, [new IntegerType(), new ObjectType($modelClassName)]);
            }

            // Specifies only value
            if (($typeMap->count() === 1) && $typeMap->hasType('TModel')) {
                return new GenericObjectType($collectionClassName, [new ObjectType($modelClassName)]);
            }
        }

        // Not generic. So return the type as is
        return new ObjectType($collectionClassName);
    }

    private function getTypeFromEloquentCollection(ClassReflection $classReflection): ?GenericObjectType
    {
        $keyType = new BenevolentUnionType([new IntegerType(), new StringType()]);

        $innerValueType = $classReflection->getActiveTemplateTypeMap()->getType('TModel');

        if ($classReflection->getName() === EloquentCollection::class || $classReflection->isSubclassOf(EloquentCollection::class)) {
            $keyType = new IntegerType();
        }

        if ($innerValueType !== null) {
            return new GenericObjectType(Collection::class, [$keyType, $innerValueType]);
        }

        return null;
    }

    private function getTypeFromIterator(ClassReflection $classReflection): GenericObjectType
    {
        $keyType = new BenevolentUnionType([new IntegerType(), new StringType()]);

        $templateTypes = array_values($classReflection->getActiveTemplateTypeMap()->getTypes());

        if (count($templateTypes) === 1) {
            return new GenericObjectType(Collection::class, [$keyType, $templateTypes[0]]);
        }

        return new GenericObjectType(Collection::class, $templateTypes);
    }
}
