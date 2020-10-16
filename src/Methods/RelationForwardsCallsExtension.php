<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Methods;

use Illuminate\Database\Eloquent\Relations\Relation;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;

final class RelationForwardsCallsExtension implements MethodsClassReflectionExtension
{
    /** @var BuilderHelper */
    private $builderHelper;

    public function __construct(BuilderHelper $builderHelper)
    {
        $this->builderHelper = $builderHelper;
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (! $classReflection->isSubclassOf(Relation::class)) {
            return false;
        }

        /** @var ObjectType|null $relatedModel */
        $relatedModel = $classReflection->getActiveTemplateTypeMap()->getType('TRelatedModel');

        if ($relatedModel === null) {
            return false;
        }

        $returnMethodReflection = $this->builderHelper->getMethodReflectionFromBuilder(
            $classReflection,
            $methodName,
            $relatedModel->getClassName(),
            new GenericObjectType($classReflection->getName(), [$relatedModel])
        );

        return $returnMethodReflection !== null;
    }

    public function getMethod(
        ClassReflection $classReflection,
        string $methodName
    ): MethodReflection {
        /** @var ObjectType|null $relatedModel */
        $relatedModel = $classReflection->getActiveTemplateTypeMap()->getType('TRelatedModel');

        if ($relatedModel === null) {
            throw new ShouldNotHappenException(sprintf('%s does not have TRelatedModel template type. But it should.', $classReflection->getName()));
        }

        $returnMethodReflection = $this->builderHelper->getMethodReflectionFromBuilder(
            $classReflection,
            $methodName,
            $relatedModel->getClassName(),
            new GenericObjectType($classReflection->getName(), [$relatedModel])
        );

        if ($returnMethodReflection === null) {
            throw new ShouldNotHappenException(sprintf('%s does not have %s method. But it should.', $classReflection->getName(), $methodName));
        }

        return $returnMethodReflection;
    }
}
