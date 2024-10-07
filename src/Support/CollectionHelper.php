<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Iterator;
use IteratorAggregate;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
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
use Traversable;

use function array_filter;
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

    public function determineOriginalCollectionType(string $modelClassName): Type|null
    {
        if (! $this->reflectionProvider->hasClass($modelClassName)) {
            return null;
        }

        $modelReflection = $this->reflectionProvider->getClass($modelClassName);

        if (! $modelReflection->is(Model::class)) {
            return null;
        }

        /** @phpstan-ignore argument.type (Attribute class might not exist) */
        $attrs = $modelReflection->getNativeReflection()->getAttributes('Illuminate\Database\Eloquent\Attributes\CollectedBy');

        if ($attrs !== []) {
            $expr =  $attrs[0]->getArgumentsExpressions()[0];

            if ($expr instanceof ClassConstFetch && $expr->class instanceof Name) {
                return new ObjectType($expr->class->toString());
            }
        }

        return $modelReflection->getNativeMethod('newCollection')
            ->getVariants()[0]
            ->getReturnType();
    }

    public function determineCollectionType(string $modelClassName, Type|null $modelType = null): Type|null
    {
        $modelType    ??= new ObjectType($modelClassName);
        $collectionType = $this->determineOriginalCollectionType($modelClassName);

        if ($collectionType === null) {
            return null;
        }

        return TypeTraverser::map($collectionType, static function (Type $type, callable $traverse) use ($modelType): Type {
            if ($type instanceof UnionType || $type instanceof IntersectionType) {
                return $traverse($type);
            }

            $classReflections = $type->getObjectClassReflections();

            if (count($classReflections) !== 1) {
                return $type;
            }

            $classReflection = $classReflections[0];

            if (! $classReflection->is(EloquentCollection::class) || ! $classReflection->isGeneric()) {
                return $type;
            }

            $keyType = new IntegerType();
            $typeMap = $classReflection->getActiveTemplateTypeMap();

            // Specifies key and value
            if ($typeMap->count() === 2) {
                return new GenericObjectType($classReflection->getName(), [$keyType, $modelType]);
            }

            // Specifies only value
            if (($typeMap->count() === 1) && $typeMap->hasType('TModel')) {
                return new GenericObjectType($classReflection->getName(), [$modelType]);
            }

            // Specifies only key
            return new GenericObjectType($classReflection->getName(), [$keyType]);
        });
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
                1 => $this->determineCollectionType($models[0], $templateType) ?? $type,
                default => TypeCombinator::union(...array_filter(array_map(fn ($m) => $this->determineCollectionType($m), $models))),
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
