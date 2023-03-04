<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use NunoMaduro\Larastan\Support\CollectionHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

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

        if (! $templateTypeMap->hasType('TModelClass')) {
            return false;
        }

        if ($templateTypeMap->getType('TModelClass')?->getObjectClassNames() === []) {
            return false;
        }

        return $builderReflection->hasNativeMethod($methodReflection->getName());
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): ?Type {
        $returnType = ParametersAcceptorSelector::selectFromArgs($scope, $methodCall->getArgs(), $methodReflection->getVariants())->getReturnType();
        $templateTypeMap = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap();

        $modelType = $templateTypeMap->getType('TModelClass');
        if ($modelType === null) {
            return null;
        }

        $classNames = $modelType->getObjectClassNames();

        if ($classNames !== [] && $modelType->isObject()->yes() && in_array(Collection::class, $returnType->getReferencedClasses(), true)) {
            $collectionClassName = $this->collectionHelper->determineCollectionClassName($classNames[0]);

            $collectionReflection = $this->reflectionProvider->getClass($collectionClassName);

            if ($collectionReflection->isGeneric()) {
                $typeMap = $collectionReflection->getActiveTemplateTypeMap();

                // Specifies key and value
                if ($typeMap->count() === 2) {
                    return new GenericObjectType($collectionClassName, [new IntegerType(), $modelType]);
                }

                // Specifies only value
                if (($typeMap->count() === 1) && $typeMap->hasType('TModel')) {
                    return new GenericObjectType($collectionClassName, [$modelType]);
                }
            } else {
                // Not generic. So return the type as is
                return new ObjectType($collectionClassName);
            }
        }

        return null;
    }
}
