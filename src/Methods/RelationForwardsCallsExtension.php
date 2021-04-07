<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Methods;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use NunoMaduro\Larastan\Reflection\EloquentBuilderMethodReflection;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;

final class RelationForwardsCallsExtension implements MethodsClassReflectionExtension
{
    /** @var BuilderHelper */
    private $builderHelper;

    /** @var array<string, MethodReflection> */
    private $cache = [];

    public function __construct(BuilderHelper $builderHelper)
    {
        $this->builderHelper = $builderHelper;
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (array_key_exists($classReflection->getCacheKey().'-'.$methodName, $this->cache)) {
            return true;
        }

        $methodReflection = $this->findMethod($classReflection, $methodName);

        if ($methodReflection !== null) {
            $this->cache[$classReflection->getCacheKey().'-'.$methodName] = $methodReflection;

            return true;
        }

        return false;
    }

    public function getMethod(
        ClassReflection $classReflection,
        string $methodName
    ): MethodReflection {
        return $this->cache[$classReflection->getCacheKey().'-'.$methodName];
    }

    private function findMethod(ClassReflection $classReflection, string $methodName): ?MethodReflection
    {
        if (! $classReflection->isSubclassOf(Relation::class)) {
            return null;
        }

        /** @var ObjectType|null $relatedModel */
        $relatedModel = $classReflection->getActiveTemplateTypeMap()->getType('TRelatedModel');

        if ($relatedModel === null) {
            return null;
        }

        if ($relatedModel instanceof ObjectType &&
            ($classReflection->getName() !== MorphTo::class &&
                $relatedModel->getClassReflection() !== null &&
                $relatedModel->getClassReflection()->getName() === Model::class
            )
        ) {
            return null;
        }

        $returnMethodReflection = $this->builderHelper->getMethodReflectionFromBuilder(
            $classReflection,
            $methodName,
            $relatedModel->getClassName(),
            new GenericObjectType($classReflection->getName(), [$relatedModel])
        );

        if ($returnMethodReflection === null && $relatedModel->hasMethod($methodName)->yes()) {
            $originalMethodReflection = $relatedModel->getMethod($methodName, new OutOfClassScope());

            return new EloquentBuilderMethodReflection(
                $methodName, $classReflection, $originalMethodReflection,
                ParametersAcceptorSelector::selectSingle($originalMethodReflection->getVariants())->getParameters(),
                new GenericObjectType($classReflection->getName(), [$relatedModel]),
                false
            );
        }

        return $returnMethodReflection;
    }
}
