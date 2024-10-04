<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Larastan\Larastan\Support\CollectionHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;

use function count;
use function in_array;

/** @internal */
final class RelationCollectionExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(private CollectionHelper $collectionHelper)
    {
    }

    public function getClass(): string
    {
        return Relation::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        if (Str::startsWith($methodReflection->getName(), 'find')) {
            return false;
        }

        $modelType = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType('TRelatedModel');

        if ($modelType === null) {
            return false;
        }

        if (count($modelType->getObjectClassNames()) === 0) {
            return false;
        }

        return $methodReflection->getDeclaringClass()->hasNativeMethod($methodReflection->getName());
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        $modelType = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType('TRelatedModel');

        if ($modelType === null) {
            return null;
        }

        $returnType = ParametersAcceptorSelector::selectFromArgs($scope, $methodCall->getArgs(), $methodReflection->getVariants())->getReturnType();

        if (! in_array(Collection::class, $returnType->getReferencedClasses(), true)) {
            return null;
        }

        if (in_array(Collection::class, $returnType->getReferencedClasses(), true)) {
            return $this->collectionHelper->determineCollectionClass($modelType->getObjectClassNames()[0]);
        }

        return $returnType;
    }
}
