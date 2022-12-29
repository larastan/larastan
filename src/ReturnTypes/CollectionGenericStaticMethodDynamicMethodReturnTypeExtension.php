<?php

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\UnionType;

class CollectionGenericStaticMethodDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Collection::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        if ($methodReflection->getDeclaringClass()->getName() === EloquentCollection::class) {
            return in_array($methodReflection->getName(), ['find']);
        }

        return in_array($methodReflection->getName(), [
            'chunk', 'chunkWhile', 'collapse', 'combine',
            'countBy', 'crossJoin', 'flatMap', 'flip',
            'groupBy', 'keyBy', 'keys',
            'make', 'map', 'mapInto',
            'mapToDictionary', 'mapToGroups',
            'mapWithKeys', 'mergeRecursive',
            'pad', 'partition', 'pluck',
            'pop', 'random', 'shift', 'sliding', 'split',
            'splitIn', 'values', 'wrap', 'zip',
        ], true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        $returnType = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants()
        )->getReturnType();

        if (! $returnType instanceof ObjectType && ! $returnType instanceof UnionType) {
            return $returnType;
        }

        $calledOnType = $scope->getType($methodCall->var);

        if (! $calledOnType instanceof TypeWithClassName) {
            return $returnType;
        }

        $classReflection = $calledOnType->getClassReflection();

        if ($classReflection === null) {
            return $returnType;
        }

        // Special cases for methods returning single models
        if ($classReflection->getName() === EloquentCollection::class && (new ObjectType(Model::class))->isSuperTypeOf($returnType)->yes()) {
            return $returnType;
        }

        // If it's a UnionType, traverse the types and try to find a collection object type
        if ($returnType instanceof UnionType) {
            return $returnType->traverse(function (Type $type) use ($classReflection) {
                if ($type instanceof GenericObjectType && (($innerReflection = $type->getClassReflection())) !== null) {
                    return $this->handleGenericObjectType($classReflection, $innerReflection);
                }

                return $type;
            });
        }

        $returnTypeClassReflection = $returnType->getClassReflection();

        if ($returnTypeClassReflection === null) {
            return $returnType;
        }

        return $this->handleGenericObjectType($classReflection, $returnTypeClassReflection);
    }

    private function handleGenericObjectType(ClassReflection $classReflection, ClassReflection $returnTypeClassReflection): ObjectType
    {
        if ($classReflection->getActiveTemplateTypeMap()->count() !== $returnTypeClassReflection->getActiveTemplateTypeMap()->count()) {
            return new ObjectType($classReflection->getName());
        }

        $genericTypes = $returnTypeClassReflection->typeMapToList($returnTypeClassReflection->getActiveTemplateTypeMap());

        // If the key type is gonna be a model, we change it to string
        if ((new ObjectType(Model::class))->isSuperTypeOf($genericTypes[0])->yes()) {
            $genericTypes[0] = new StringType();
        }

        $genericTypes = array_map(static function (Type $type) use ($classReflection) {
            return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($classReflection): Type {
                if ($type instanceof UnionType || $type instanceof IntersectionType) {
                    return $traverse($type);
                }

                if ($type instanceof GenericObjectType && (($innerTypeReflection = $type->getClassReflection()) !== null)) {
                    $genericTypes = $innerTypeReflection->typeMapToList($innerTypeReflection->getActiveTemplateTypeMap());

                    return new GenericObjectType($classReflection->getName(), $genericTypes);
                }

                return $traverse($type);
            });
        }, $genericTypes);

        return new GenericObjectType($classReflection->getName(), $genericTypes);
    }
}
