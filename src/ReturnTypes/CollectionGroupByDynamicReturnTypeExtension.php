<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function count;

#[AutowiredService]
class CollectionGroupByDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Collection::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        // A collection that overrides the method has its own contract.
        return $methodReflection->getName() === 'groupBy'
            && $methodReflection->getDeclaringClass()->getName() === Collection::class;
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        $args = $methodCall->getArgs();

        // Grouping by several keys nests further levels, so it is left to the stub.
        if ($args === [] || $args[0]->unpack || ! $scope->getType($args[0]->value)->isArray()->no()) {
            return null;
        }

        $calledOnType = TypeCombinator::intersect(
            $scope->getType($methodCall->var),
            new ObjectType(Collection::class),
        );

        $classReflections = $calledOnType->getObjectClassReflections();

        if (count($classReflections) !== 1 || $classReflections[0]->isGeneric()) {
            return null;
        }

        $baseCollectionType = new GenericObjectType(
            $classReflections[0]->is(EloquentCollection::class) ? EloquentCollection::class : Collection::class,
            [
                $calledOnType->getTemplateType(Collection::class, 'TKey'),
                $calledOnType->getTemplateType(Collection::class, 'TValue'),
            ],
        );

        if (! $baseCollectionType->hasMethod('groupBy')->yes()) {
            return null;
        }

        $baseMethodReflection = $baseCollectionType->getMethod('groupBy', $scope);

        $groupedType = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $args,
            $baseMethodReflection->getVariants(),
            $baseMethodReflection->getNamedArgumentsVariants(),
        )->getReturnType();

        // Without templates, the stub's static<...> resolves to the receiver itself, which is the type of each group.
        $groupType = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $args,
            $methodReflection->getVariants(),
        )->getReturnType();

        return new GenericObjectType($baseCollectionType->getClassName(), [
            $groupedType->getTemplateType(Collection::class, 'TKey'),
            $groupType,
        ]);
    }
}
