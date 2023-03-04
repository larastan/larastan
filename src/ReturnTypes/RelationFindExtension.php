<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use NunoMaduro\Larastan\Support\CollectionHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * @internal
 */
final class RelationFindExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(private ReflectionProvider $reflectionProvider, private CollectionHelper $collectionHelper)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getClass(): string
    {
        return Relation::class;
    }

    /**
     * {@inheritdoc}
     */
    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        if (! Str::startsWith($methodReflection->getName(), 'find')) {
            return false;
        }

        $modelType = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType('TRelatedModel');

        if (! $modelType) {
            return false;
        }

        if ($modelType->getObjectClassNames() === []) {
            return false;
        }

        return $methodReflection->getDeclaringClass()->hasNativeMethod($methodReflection->getName()) ||
            $this->reflectionProvider->getClass(Builder::class)->hasNativeMethod($methodReflection->getName()) ||
            $this->reflectionProvider->getClass(QueryBuilder::class)->hasNativeMethod($methodReflection->getName());
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): ?Type {
        $modelType = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType('TRelatedModel');
        if ($modelType === null) {
            return null;
        }

        $modelName = $modelType->getObjectClassNames()[0];

        $argType = $scope->getType($methodCall->getArgs()[0]->value);

        $returnType = $methodReflection->getVariants()[0]->getReturnType();

        if (in_array(Collection::class, $returnType->getReferencedClasses(), true)) {
            if ($argType->isIterable()->yes()) {
                return $this->collectionHelper->determineCollectionClass($modelName);
            }

            $returnType = TypeCombinator::remove($returnType, new ObjectType(Collection::class));

            return TypeCombinator::remove($returnType, new ArrayType(new MixedType(), $modelType));
        }

        return $returnType;
    }
}
