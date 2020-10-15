<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Methods;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use NunoMaduro\Larastan\Concerns;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;

final class ModelForwardsCallsExtension implements MethodsClassReflectionExtension, BrokerAwareExtension
{
    use Concerns\HasBroker;

    private function getBuilderReflection(): ClassReflection
    {
        return $this->broker->getClass(EloquentBuilder::class);
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->getName() !== Model::class && ! $classReflection->isSubclassOf(Model::class)) {
            return false;
        }

        $builderHelper = new BuilderHelper($this->getBroker());
        $customBuilderName = $builderHelper->determineBuilderType($classReflection->getName());

        $returnMethodReflection = $builderHelper->getMethodReflectionFromBuilder(
            $classReflection,
            $methodName,
            $classReflection->getName(),
            new GenericObjectType($customBuilderName, [new ObjectType($classReflection->getName())])
        );

        return $returnMethodReflection !== null;
    }

    /**
     * @param ClassReflection $originalModelReflection
     * @param string          $methodName
     *
     * @return MethodReflection
     * @throws ShouldNotHappenException
     * @throws MissingMethodFromReflectionException
     */
    public function getMethod(ClassReflection $originalModelReflection, string $methodName): MethodReflection
    {
        $builderHelper = new BuilderHelper($this->getBroker());
        $customBuilderName = $builderHelper->determineBuilderType($originalModelReflection->getName());

        $returnMethodReflection = $builderHelper->getMethodReflectionFromBuilder(
            $originalModelReflection,
            $methodName,
            $originalModelReflection->getName(),
            new GenericObjectType($customBuilderName, [new ObjectType($originalModelReflection->getName())])
        );

        if ($returnMethodReflection === null) {
            throw new ShouldNotHappenException();
        }

        return $returnMethodReflection;
    }
}
