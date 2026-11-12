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
        if ($methodCall->getArgs() === []) {
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

        $baseMethodReflection = $baseCollectionType->getMethod('groupBy', $scope);

        $groupedType = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $baseMethodReflection->getVariants(),
            $baseMethodReflection->getNamedArgumentsVariants(),
        )->getReturnType();

        // Grouping by several keys nests further levels, so the groups are not instances of the receiver.
        if (! $scope->getType($methodCall->getArgs()[0]->value)->isArray()->no()) {
            return $groupedType;
        }

        // Without templates, the stub's static<...> resolves to the receiver itself, which is the type of each group.
        $groupType = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants(),
        )->getReturnType();

        return new GenericObjectType($baseCollectionType->getClassName(), [
            $groupedType->getTemplateType(Collection::class, 'TKey'),
            $groupType,
        ]);
    }
}
