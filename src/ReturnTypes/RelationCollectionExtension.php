<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Larastan\Larastan\Internal\LaravelVersion;
use Larastan\Larastan\Support\CollectionHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;

use function in_array;

/** @internal */
final class RelationCollectionExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private CollectionHelper $collectionHelper,
    ) {
    }

    public function getClass(): string
    {
        return Relation::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        $modelType = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType('TRelatedModel');

        if ($modelType === null && $methodReflection->getDeclaringClass()->getName() === Builder::class) {
            $modelType = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType(LaravelVersion::getBuilderModelGenericName());
        }

        if ($modelType === null || $modelType->getObjectClassNames() === []) {
            return false;
        }

        return $methodReflection->getDeclaringClass()->hasNativeMethod($methodReflection->getName()) ||
            $this->reflectionProvider->getClass(Builder::class)->hasNativeMethod($methodReflection->getName()) ||
            $this->reflectionProvider->getClass(QueryBuilder::class)->hasNativeMethod($methodReflection->getName());
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        $returnType = ParametersAcceptorSelector::selectFromArgs($scope, $methodCall->getArgs(), $methodReflection->getVariants())->getReturnType();

        if (! in_array(Collection::class, $returnType->getReferencedClasses(), true)) {
            return null;
        }

        return $this->collectionHelper->replaceCollectionsInType($returnType);
    }
}
