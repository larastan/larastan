<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use NunoMaduro\Larastan\Concerns;
use NunoMaduro\Larastan\Methods\BuilderHelper;
use NunoMaduro\Larastan\Methods\ModelTypeHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\Dummy\DummyMethodReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;

final class EloquentBuilderExtension implements DynamicMethodReturnTypeExtension, BrokerAwareExtension
{
    use Concerns\HasBroker;

    public function getClass(): string
    {
        return EloquentBuilder::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        $builderReflection = $this->getBroker()->getClass(EloquentBuilder::class);

        // Don't handle dynamic wheres
        if (Str::startsWith($methodReflection->getName(), 'where') &&
            ! $builderReflection->hasNativeMethod($methodReflection->getName())
        ) {
            return false;
        }

        if (Str::startsWith($methodReflection->getName(), 'find') &&
            $builderReflection->hasNativeMethod($methodReflection->getName())
        ) {
            return false;
        }

        $templateTypeMap = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap();

        if (! $templateTypeMap->getType('TModelClass') instanceof ObjectType) {
            return false;
        }

        return $builderReflection->hasNativeMethod($methodReflection->getName());
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        $returnType = $methodReflection->getVariants()[0]->getReturnType();
        $templateTypeMap = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap();

        /** @var Type|ObjectType|TemplateMixedType $modelType */
        $modelType = $templateTypeMap->getType('TModelClass');

        if ($methodReflection instanceof DummyMethodReflection && $modelType instanceof ObjectType) {
            $scopeMethodName = 'scope'.ucfirst($methodReflection->getName());
            $modelReflection = $this->getBroker()->getClass($modelType->getClassName());

            if ($modelReflection->hasNativeMethod($scopeMethodName)) {
                return new ObjectType(EloquentBuilder::class);
            }

            if ($modelReflection->hasNativeMethod('newEloquentBuilder')) {
                $customBuilder = $modelReflection->getNativeMethod('newEloquentBuilder')->getVariants()[0]->getReturnType();

                if ($customBuilder->hasMethod($methodReflection->getName())->yes()) {
                    return $customBuilder;
                }
            }
        }

        if (($modelType instanceof ObjectType || $modelType instanceof ThisType) && in_array($methodReflection->getName(), array_merge(BuilderHelper::MODEL_CREATION_METHODS, BuilderHelper::MODEL_RETRIEVAL_METHODS), true)) {
            $returnType = ModelTypeHelper::replaceStaticTypeWithModel($methodReflection->getVariants()[0]->getReturnType(), $modelType->getClassName());
        }

        if (($modelType instanceof ObjectType || $modelType instanceof ThisType) && in_array(EloquentBuilder::class, $returnType->getReferencedClasses(), true)) {
            $builderHelper = new BuilderHelper($this->getBroker());

            $returnType = new GenericObjectType(
                $builderHelper->determineBuilderType($modelType->getClassName()) ?? EloquentBuilder::class,
                [$modelType]
            );
        }

        // 'get' method return type
        if ($methodReflection->getName() === 'get') {
            return new GenericObjectType(Collection::class, [$modelType]);
        }

        return $returnType;
    }
}
