<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Iterator;
use IteratorAggregate;
use Larastan\Larastan\Internal\LaravelVersion;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
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
            $modelReflection = $this->reflectionProvider->getClass($modelClassName);

            if (LaravelVersion::hasCollectedByAttribute()) {
                $attrs = $modelReflection->getNativeReflection()->getAttributes('Illuminate\Database\Eloquent\Attributes\CollectedBy'); // @phpstan-ignore-line Attribute class is not available in older Laravel versions

                if ($attrs !== []) {
                    $expr =  $attrs[0]->getArgumentsExpressions()[0];

                    if ($expr instanceof ClassConstFetch && $expr->class instanceof Name) {
                        return $expr->class->toString();
                    }
                }
            }

            $newCollectionMethod = $modelReflection->getNativeMethod('newCollection');
            $returnType          = $newCollectionMethod->getVariants()[0]->getReturnType();

            $classNames = $returnType->getObjectClassNames();

            if (count($classNames) === 1) {
                return $classNames[0];
            }

            return $returnType->describe(VerbosityLevel::value());
        } catch (MissingMethodFromReflectionException | ShouldNotHappenException) {
            return EloquentCollection::class;
        }
    }

    public function determineCollectionClass(string $modelClassName, Type|null $modelType = null): Type
    {
        $modelType ??= new ObjectType($modelClassName);

        $collectionClassName  = $this->determineCollectionClassName($modelClassName);
        $collectionReflection = $this->reflectionProvider->getClass($collectionClassName);

        if ($collectionReflection->isGeneric()) {
            $typeMap = $collectionReflection->getActiveTemplateTypeMap();

            // Specifies key and value
            if ($typeMap->count() === 2) {
                return new GenericObjectType($collectionClassName, [new IntegerType(), $modelType]);
            }

            // Specifies only value
            if (($typeMap->count() === 1) && $typeMap->hasType('TModel')) {
                return new GenericObjectType($collectionClassName, [$modelType]);
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

            $templateType = $type->getTemplateType(EloquentCollection::class, 'TModel');
            $models       = $templateType->getObjectClassNames();

            return match (count($models)) {
                0 => $type,
                1 => $this->determineCollectionClass($models[0], $templateType),
                default => TypeCombinator::union(...array_map(fn ($m) => $this->determineCollectionClass($m), $models)),
            };
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
