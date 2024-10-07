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
use PHPStan\Type\TypeCombinator;

use function collect;
use function count;
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

        // Don't handle dynamic wheres
        if (
            Str::startsWith($methodReflection->getName(), 'where') &&
            ! $builderReflection->hasNativeMethod($methodReflection->getName())
        ) {
            return false;
        }

        if (
            Str::startsWith($methodReflection->getName(), 'find') &&
            $builderReflection->hasNativeMethod($methodReflection->getName())
        ) {
            return false;
        }

        $templateTypeMap = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap();

        if (! $templateTypeMap->hasType('TModel')) {
            return false;
        }

        if ($templateTypeMap->getType('TModel')?->getObjectClassNames() === []) {
            return false;
        }

        return $builderReflection->hasNativeMethod($methodReflection->getName());
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        $returnType      = ParametersAcceptorSelector::selectFromArgs($scope, $methodCall->getArgs(), $methodReflection->getVariants())->getReturnType();
        $templateTypeMap = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap();

        $modelType = $templateTypeMap->getType('TModel');
        if ($modelType === null) {
            return null;
        }

        $classNames = $modelType->getObjectClassNames();

        if (count($classNames) === 0) {
            return null;
        }

        if (! in_array(Collection::class, $returnType->getReferencedClasses(), true)) {
            return null;
        }

        return TypeCombinator::union(
            ...collect($classNames)
                ->map(fn ($className) => $this->collectionHelper->determineCollectionType($className))
                ->filter()
                ->all(),
        );
    }
}
