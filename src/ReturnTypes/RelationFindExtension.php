<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use NunoMaduro\Larastan\Concerns;
use NunoMaduro\Larastan\Methods\BuilderHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * @internal
 */
final class RelationFindExtension implements DynamicMethodReturnTypeExtension, BrokerAwareExtension
{
    use Concerns\HasBroker;

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

        if (! $modelType instanceof ObjectType) {
            return false;
        }

        return $methodReflection->getDeclaringClass()->hasNativeMethod($methodReflection->getName()) ||
            $this->getBroker()->getClass(Builder::class)->hasNativeMethod($methodReflection->getName()) ||
            $this->getBroker()->getClass(QueryBuilder::class)->hasNativeMethod($methodReflection->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        /** @var ObjectType $modelType */
        $modelType = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType('TRelatedModel');

        $argType = $scope->getType($methodCall->args[0]->value);

        $returnType = $methodReflection->getVariants()[0]->getReturnType();

        if (in_array(Collection::class, $returnType->getReferencedClasses(), true)) {
            if ($argType->isIterable()->yes()) {
                $builderHelper = new BuilderHelper($this->getBroker());

                $collectionClassName = $builderHelper->determineCollectionClassName($modelType->getClassname());

                return new GenericObjectType($collectionClassName, [$modelType]);
            }

            $returnType = TypeCombinator::remove($returnType, new ObjectType(Collection::class));

            return TypeCombinator::remove($returnType, new ArrayType(new MixedType(), $modelType));
        }

        return $returnType;
    }
}
