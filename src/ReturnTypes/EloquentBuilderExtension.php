<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Larastan\Larastan\Support\CollectionHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;

use function in_array;

final class EloquentBuilderExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(private ReflectionProvider $reflectionProvider, private CollectionHelper $collectionHelper)
    {
    }

    public function getClass(): string
    {
        return EloquentBuilder::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        $builderReflection = $this->reflectionProvider->getClass(EloquentBuilder::class);
        $hasNativeMethod   = $builderReflection->hasNativeMethod($methodReflection->getName());

        // Don't handle dynamic wheres
        if (Str::startsWith($methodReflection->getName(), 'where') && ! $hasNativeMethod) {
            return false;
        }

        if (Str::startsWith($methodReflection->getName(), 'find') && $hasNativeMethod) {
            return false;
        }

        $templateTypeMap = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap();

        if (! $templateTypeMap->hasType('TModel')) {
            return false;
        }

        if ($templateTypeMap->getType('TModel')?->getObjectClassNames() === []) {
            return false;
        }

        return $hasNativeMethod;
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        $returnType = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants(),
        )->getReturnType();

        if (in_array(Collection::class, $returnType->getReferencedClasses(), true)) {
            $modelType = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType('TModel');

            if ($modelType === null) {
                return null;
            }

            return $this->collectionHelper->determineCollectionClass($modelType->getObjectClassNames()[0]);
        }

        return null;
    }
}
