<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\HasCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Iterator;
use IteratorAggregate;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use Traversable;

use function array_map;
use function array_values;
use function count;
use function in_array;
use function ltrim;
use function version_compare;

final class CollectionHelper
{
    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    public function determineGenericCollectionTypeFromType(Type $type): GenericObjectType|null
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
            $modelReflection     = $this->reflectionProvider->getClass($modelClassName);
            $newCollectionMethod = $modelReflection->getNativeMethod('newCollection');

            // The attribute is read by HasCollection::newCollection(), so it has no effect when that method is overridden.
            $newCollectionFileName = $newCollectionMethod->getDeclaringClass()->getNativeReflection()->getMethod('newCollection')->getFileName();

            if ($newCollectionFileName === $this->reflectionProvider->getClass(HasCollection::class)->getFileName()) {
                $collectionClassName = $this->findCollectedByClassName($modelReflection);

                if ($collectionClassName !== null) {
                    return $collectionClassName;
                }
            }

            $returnType = $newCollectionMethod->getVariants()[0]->getReturnType();

            $classNames = $returnType->getObjectClassNames();

            if (count($classNames) === 1) {
                return $classNames[0];
            }

            return $returnType->describe(VerbosityLevel::value());
        } catch (MissingMethodFromReflectionException | ShouldNotHappenException) {
            return EloquentCollection::class;
        }
    }

    /**
     * Mirrors HasCollection::resolveCollectionFromAttribute(), which only reads a positional argument.
     * Since Laravel 13.4, the parent classes are searched too, nearest first.
     */
    private function findCollectedByClassName(ClassReflection $modelReflection): string|null
    {
        // @phpstan-ignore ternary.alwaysTrue, ternary.alwaysFalse
        $classReflections = version_compare(LARAVEL_VERSION, '13.4.0', '>=')
            ? [$modelReflection, ...$modelReflection->getParents()]
            : [$modelReflection];

        foreach ($classReflections as $classReflection) {
            $attributes = $classReflection->getNativeReflection()->getAttributes('Illuminate\Database\Eloquent\Attributes\CollectedBy'); //@phpstan-ignore argument.type (Attribute class might not exist)
            $expr       = ($attributes[0] ?? null)?->getArgumentsExpressions()[0] ?? null;

            if ($expr === null) {
                continue;
            }

            if ($expr instanceof ClassConstFetch && $expr->class instanceof Name) {
                return $expr->class->toString();
            }

            if ($expr instanceof String_) {
                return ltrim($expr->value, '\\');
            }

            return null;
        }

        return null;
    }

    public function determineCollectionClass(string $modelClassName, Type|null $modelType = null, Type|null $keyType = null): Type
    {
        $collectionClassName  = $this->determineCollectionClassName($modelClassName);
        $collectionReflection = $this->reflectionProvider->getClass($collectionClassName);

        if ($collectionReflection->isGeneric()) {
            $typeMap = $collectionReflection->getActiveTemplateTypeMap();

            // Specifies key and value
            if ($typeMap->count() === 2) {
                return new GenericObjectType($collectionClassName, [$keyType ?? new IntegerType(), $modelType ?? new ObjectType($modelClassName)]);
            }

            // Specifies only value
            if (($typeMap->count() === 1) && $typeMap->hasType('TModel')) {
                return new GenericObjectType($collectionClassName, [$modelType ?? new ObjectType($modelClassName)]);
            }
        }

        // Not generic. So return the type as is
        return new ObjectType($collectionClassName);
    }

    public function replaceCollectionsInType(Type $type): Type
    {
        if (! in_array(EloquentCollection::class, $type->getReferencedClasses(), true)) {
            return $type;
        }

        return TypeTraverser::map($type, function ($type, $traverse): Type {
            if ($type instanceof UnionType || $type instanceof IntersectionType) {
                return $traverse($type);
            }

            if (! (new ObjectType(EloquentCollection::class))->isSuperTypeOf($type)->yes()) {
                return $traverse($type);
            }

            $models = $type->getTemplateType(EloquentCollection::class, 'TModel')->getObjectClassNames();

            if (count($models) === 0) {
                return $type;
            }

            return TypeCombinator::union(...array_map([$this, 'determineCollectionClass'], $models));
        });
    }

    private function getTypeFromEloquentCollection(ClassReflection $classReflection): GenericObjectType|null
    {
        $keyType = new BenevolentUnionType([new IntegerType(), new StringType()]);

        $innerValueType = $classReflection->getActiveTemplateTypeMap()->getType('TModel');

        if ($classReflection->is(EloquentCollection::class)) {
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
