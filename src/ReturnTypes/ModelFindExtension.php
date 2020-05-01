<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use NunoMaduro\Larastan\Concerns;
use NunoMaduro\Larastan\Methods\BuilderHelper;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * @internal
 */
final class ModelFindExtension implements DynamicStaticMethodReturnTypeExtension, BrokerAwareExtension
{
    use Concerns\HasBroker;

    /**
     * {@inheritdoc}
     */
    public function getClass(): string
    {
        return Model::class;
    }

    /**
     * {@inheritdoc}
     */
    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        $methodName = $methodReflection->getName();

        if (! Str::startsWith($methodName, 'find')) {
            return false;
        }

        if (! $this->getBroker()->getClass(Builder::class)->hasNativeMethod($methodName) &&
            ! $this->getBroker()->getClass(QueryBuilder::class)->hasNativeMethod($methodName)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope
    ): Type {
        $modelName = $methodReflection->getDeclaringClass()->getName();
        $returnType = $methodReflection->getVariants()[0]->getReturnType();
        $argType = $scope->getType($methodCall->args[0]->value);

        if ($argType->isIterable()->yes()) {
            if (in_array(Collection::class, $returnType->getReferencedClasses(), true)) {
                $builderHelper = new BuilderHelper($this->getBroker());

                $collectionClassName = $builderHelper->determineCollectionClassName($modelName);

                return new GenericObjectType($collectionClassName, [new ObjectType($modelName)]);
            }

            return TypeCombinator::remove($returnType, new ObjectType($modelName));
        }

        if ($argType instanceof MixedType) {
            return $returnType;
        }

        return TypeCombinator::remove(
            TypeCombinator::remove(
                $returnType,
                new ArrayType(new MixedType(), new ObjectType($modelName))
            ),
            new ObjectType(Collection::class)
        );
    }
}
