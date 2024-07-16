<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Str;
use Larastan\Larastan\Support\CollectionHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;

final class EloquentBuilderExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private CollectionHelper $collectionHelper,
    ) {
    }

    public function getClass(): string
    {
        return EloquentBuilder::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        $methodName        = $methodReflection->getName();
        $builderReflection = $this->reflectionProvider->getClass(EloquentBuilder::class);
        $hasNativeMethod   = $builderReflection->hasNativeMethod($methodName);

        // Don't handle dynamic wheres
        if (Str::startsWith($methodName, 'where') && ! $hasNativeMethod) {
            return false;
        }

        if (Str::startsWith($methodName, 'find') && $hasNativeMethod) {
            return false;
        }

        return $hasNativeMethod;
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

        return $this->collectionHelper->replaceCollectionsInType($returnType);
    }
}
