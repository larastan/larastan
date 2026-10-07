<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function in_array;

class EloquentCollectionMapDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return EloquentCollection::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        // A collection that overrides the method has its own contract.
        return in_array($methodReflection->getName(), ['map', 'mapWithKeys'], true)
            && $methodReflection->getDeclaringClass()->getName() === EloquentCollection::class;
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        if ($methodCall->getArgs() === []) {
            return null;
        }

        // The receiver can be a union with other types that have these methods.
        $calledOnType = TypeCombinator::intersect(
            $scope->getType($methodCall->var),
            new ObjectType(EloquentCollection::class),
        );

        // Same as the runtime: map the items as a base collection, then decide which class is kept.
        $baseCollectionType = new GenericObjectType(Collection::class, [
            $calledOnType->getTemplateType(Collection::class, 'TKey'),
            $calledOnType->getTemplateType(Collection::class, 'TValue'),
        ]);

        if (! $baseCollectionType->hasMethod($methodReflection->getName())->yes()) {
            return null;
        }

        $baseMethodReflection = $baseCollectionType->getMethod($methodReflection->getName(), $scope);

        $mappedType = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $baseMethodReflection->getVariants(),
            $baseMethodReflection->getNamedArgumentsVariants(),
        )->getReturnType();

        $mappedValueType = $mappedType->getTemplateType(Collection::class, 'TValue');

        if (! (new ObjectType(Model::class))->isSuperTypeOf($mappedValueType)->maybe()) {
            return null;
        }

        return $mappedType;
    }
}
