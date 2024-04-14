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
use PHPStan\Type\IntersectionType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;

use function array_map;
use function array_merge;
use function in_array;

class EnumerableGenericStaticMethodDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Enumerable::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        if ($methodReflection->getDeclaringClass()->getName() === EloquentCollection::class) {
            return $methodReflection->getName() === 'find';
        }

        $methods = [
            'chunk',
            'chunkWhile',
            'collapse',
            'combine',
            'concat',
            'crossJoin',
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
            'pad',
            'partition',
            'pluck',
            'random',
            'sliding',
            'split',
            'splitIn',
            'values',
            'wrap',
            'zip',
        ];

        if ($methodReflection->getDeclaringClass()->getName() === Collection::class) {
            $methods = array_merge($methods, ['pop', 'shift']);
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

        $calledOnType = $scope->getType($methodCall->var);

        if ($calledOnType->getObjectClassReflections() === []) {
            return $returnType;
        }

        $classReflection = $calledOnType->getObjectClassReflections()[0];

        // Special cases for methods returning single models
        if ((new ObjectType(Model::class))->isSuperTypeOf($returnType)->yes()) {
            return $returnType;
        }

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

    private function handleGenericObjectType(ClassReflection $classReflection, ClassReflection $returnTypeClassReflection): ObjectType
    {
        if ($classReflection->getActiveTemplateTypeMap()->count() !== $returnTypeClassReflection->getActiveTemplateTypeMap()->count()) {
            return new ObjectType($classReflection->getName());
        }

        $genericTypes = $returnTypeClassReflection->typeMapToList($returnTypeClassReflection->getActiveTemplateTypeMap());

        if ($genericTypes === []) {
            return new ObjectType($classReflection->getName());
        }

        // If the key type is gonna be a model, we change it to string
        if ((new ObjectType(Model::class))->isSuperTypeOf($genericTypes[0])->yes()) {
            $genericTypes[0] = new StringType();
        }

        $genericTypes = array_map(static function (Type $type) use ($classReflection) {
            return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($classReflection): Type {
                if ($type instanceof UnionType || $type instanceof IntersectionType) {
                    return $traverse($type);
                }

                // @phpcs:ignore
                if ($type instanceof GenericObjectType && (($innerTypeReflection = $type->getClassReflection()) !== null)) {
                    $genericTypes = $innerTypeReflection->typeMapToList($innerTypeReflection->getActiveTemplateTypeMap());

                    if ($classReflection->isSubclassOf($type->getClassName())) {
                        return new GenericObjectType($classReflection->getName(), $genericTypes);
                    }
                }

                return $traverse($type);
            });
        }, $genericTypes);

        return new GenericObjectType($classReflection->getName(), $genericTypes);
    }
}
