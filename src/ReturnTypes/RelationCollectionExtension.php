<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use NunoMaduro\Larastan\Support\CollectionHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * @internal
 */
final class RelationCollectionExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(private CollectionHelper $collectionHelper)
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

        $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

        if (! in_array(Collection::class, $returnType->getReferencedClasses(), true)) {
            return false;
        }

        return $methodReflection->getDeclaringClass()->hasNativeMethod($methodReflection->getName());
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

        $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

        if (in_array(Collection::class, $returnType->getReferencedClasses(), true)) {
            return $this->collectionHelper->determineCollectionClass($modelType->getClassname());
        }

        return $returnType;
    }
}
