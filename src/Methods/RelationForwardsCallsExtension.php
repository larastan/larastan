<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Methods;

use Illuminate\Database\Eloquent\Relations\Relation;
use NunoMaduro\Larastan\Concerns\HasBroker;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\DummyMethodReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;

final class RelationForwardsCallsExtension implements MethodsClassReflectionExtension, BrokerAwareExtension
{
    use HasBroker;

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (! $classReflection->isSubclassOf(Relation::class)) {
            return false;
        }

        return $classReflection->getActiveTemplateTypeMap()->getType('TRelatedModel') !== null;
    }

    public function getMethod(
        ClassReflection $classReflection,
        string $methodName
    ): MethodReflection {
        $builderHelper = new BuilderHelper($this->getBroker());

        /** @var ObjectType|null $relatedModel */
        $relatedModel = $classReflection->getActiveTemplateTypeMap()->getType('TRelatedModel');

        if ($relatedModel === null) {
            return new DummyMethodReflection($methodName);
        }

        $returnMethodReflection = $builderHelper->getMethodReflectionFromBuilder(
            $classReflection,
            $methodName,
            $relatedModel->getClassName(),
            new GenericObjectType($classReflection->getName(), [$relatedModel])
        );

        if ($returnMethodReflection !== null) {
            return $returnMethodReflection;
        }

        return new DummyMethodReflection($methodName);
    }
}
