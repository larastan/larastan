<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateObjectType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;

use function array_map;
use function array_merge;
use function in_array;

class EnumerableGenericDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Enumerable::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        $methods = [
            'chunk',
            'chunkWhile',
            'collapse',
            'combine',
            'concat',
            'crossJoin',
            'except',
            'flatMap',
            'flip',
            'groupBy',
            'keyBy',
            'keys',
            'map',
            'mapInto',
            'mapToDictionary',
            'mapToGroups',
            'mapWithKeys',
            'mergeRecursive',
            'only',
            'pad',
            'partition',
            'pluck',
            'random',
            'sliding',
            'split',
            'splitIn',
            'unique',
            'values',
            'wrap',
            'zip',
        ];

        if ($methodReflection->getDeclaringClass()->getName() === Collection::class) {
            $methods = array_merge($methods, ['pop', 'shift']);
        }

        if ($methodReflection->getDeclaringClass()->getName() === EloquentCollection::class) {
            $methods = array_merge($methods, ['find']);
        }

        return in_array($methodReflection->getName(), $methods, true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type {
        $returnType = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants(),
        )->getReturnType();

        if ((! $returnType instanceof UnionType) && $returnType->isObject()->no()) {
            return $returnType;
        }

        // Special cases for methods returning single models
        if ((new ObjectType(Model::class))->isSuperTypeOf($returnType)->yes()) {
            return $returnType;
        }

        $calledOnType = $scope->getType($methodCall->var);

        // should never happen
        if ($calledOnType->getObjectClassReflections() === []) {
            return $returnType;
        }

        $classReflection = $calledOnType->getObjectClassReflections()[0];

        // If it's a UnionType, traverse the types and try to find a collection object type
        if ($returnType instanceof UnionType) {
            return $returnType->traverse(function (Type $type) use ($classReflection) {
                // @phpcs:ignore
                if ($type instanceof GenericObjectType && ($innerReflection = $type->getClassReflection()) !== null) { // @phpstan-ignore-line
                    return $this->handleGenericObjectType($classReflection, $innerReflection);
                }

                return $type;
            });
        }

        if ($returnType->getObjectClassReflections() === []) {
            return $returnType;
        }

        return $this->handleGenericObjectType($classReflection, $returnType->getObjectClassReflections()[0]);
    }

    private function handleGenericObjectType(
        ClassReflection $classReflection,
        ClassReflection $returnTypeClassReflection,
    ): ObjectType {
        $genericTypes = $returnTypeClassReflection->typeMapToList($returnTypeClassReflection->getActiveTemplateTypeMap());

        if ($genericTypes === []) {
            if ($classReflection->isSubclassOf($returnTypeClassReflection->getName())) {
                return new ObjectType($classReflection->getName());
            }

            return new ObjectType($returnTypeClassReflection->getName());
        }

        // If the key type is gonna be a model, we change it to string
        if ((new ObjectType(Model::class))->isSuperTypeOf($genericTypes[0])->yes()) {
            $genericTypes[0] = new StringType();
        }

        $genericTypes = array_map(function (Type $type) use ($classReflection) {
            return TypeTraverser::map($type, function (Type $type, callable $traverse) use ($classReflection): Type {
                if ($type instanceof UnionType || $type instanceof IntersectionType) {
                    return $traverse($type);
                }

                // @phpcs:ignore
                if ($type instanceof GenericObjectType && (($innerTypeReflection = $type->getClassReflection()) !== null)) {
                    $genericTypes = $innerTypeReflection->typeMapToList($innerTypeReflection->getActiveTemplateTypeMap());

                    if ($classReflection->isSubclassOf($type->getClassName())) {
                        return $this->getCollectionType($classReflection, $innerTypeReflection, $genericTypes);
                    }
                }

                return $traverse($type);
            });
        }, $genericTypes);

        // need to account for other generic classes
        if (
            $returnTypeClassReflection->isClass()
            && ! $classReflection->isSubclassOf($returnTypeClassReflection->getName())
        ) {
            return new GenericObjectType($returnTypeClassReflection->getName(), $genericTypes);
        }

        return $this->getCollectionType($classReflection, $returnTypeClassReflection, $genericTypes);
    }

    /** @param array<int, Type> $genericTypes */
    private function getCollectionType(
        ClassReflection $classReflection,
        ClassReflection $returnTypeClassReflection,
        array $genericTypes,
    ): ObjectType {
        $isGeneric       = true;
        $collectionClass = $classReflection->getName();

        if ($classReflection->getActiveTemplateTypeMap()->count() === 0) {
            $isGeneric = false;
        }

        if (
            ($classReflection->getName() === EloquentCollection::class
            || $classReflection->isSubclassOf(EloquentCollection::class))
        ) {
            $modelType  = new ObjectType(Model::class);
            $tModelType = $classReflection->getTemplateTypeMap()->getType('TModel')
                ?? $classReflection->getParentClass()?->getActiveTemplateTypeMap()->getType('TModel');

            if ($tModelType instanceof TemplateObjectType) {
                $tModelType = $tModelType->getBound();
            }

            // An Eloquent Collection can only contain models.
            if (! $modelType->isSuperTypeOf($genericTypes[1])->yes()) {
                $collectionClass = Collection::class;
                $isGeneric       = true;
            // An Eloquent Collection cannot contain a model that is wider than its template type.
            } elseif ($tModelType !== null && ! $tModelType->isSuperTypeOf($genericTypes[1])->yes()) {
                $collectionClass = EloquentCollection::class;
                $isGeneric       = true;
            }
        }

        return $isGeneric
            ? new GenericObjectType($collectionClass, $genericTypes)
            : new ObjectType($collectionClass);
    }
}
