<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Methods;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use NunoMaduro\Larastan\Concerns;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\DummyMethodReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;

final class ModelForwardsCallsExtension implements MethodsClassReflectionExtension, BrokerAwareExtension
{
    use Concerns\HasBroker;

    /** @var string[] */
    public const MODEL_RETRIEVAL_METHODS = ['first', 'find', 'findMany', 'findOrFail', 'firstOrFail'];

    /** @var string[] */
    public const MODEL_CREATION_METHODS = ['make', 'create', 'forceCreate', 'findOrNew', 'firstOrNew', 'updateOrCreate', 'fromQuery', 'firstOrCreate'];

    private function getBuilderReflection(): ClassReflection
    {
        return $this->broker->getClass(EloquentBuilder::class);
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (! $classReflection->isSubclassOf(Model::class)) {
            return false;
        }

        if (in_array($methodName, ['increment', 'decrement', 'paginate', 'simplePaginate'], true)) {
            return true;
        }

        // Model scopes
        if ($classReflection->hasNativeMethod('scope'.ucfirst($methodName))) {
            return true;
        }

        // Dynamic wheres
        if (Str::startsWith($methodName, 'where')) {
            return true;
        }

        $builderHelper = new BuilderHelper($this->getBroker());
        $customBuilder = $builderHelper->determineBuilderType($classReflection->getName());

        return $this->getBuilderReflection()->hasNativeMethod($methodName)
            || $this->broker->getClass(QueryBuilder::class)->hasNativeMethod($methodName)
            || ($customBuilder && $this->broker->getClass($customBuilder)->hasNativeMethod($methodName));
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
            new GenericObjectType($customBuilderName ?? EloquentBuilder::class, [new ObjectType($originalModelReflection->getName())])
        );

        if ($returnMethodReflection !== null) {
            return $returnMethodReflection;
        }

        return new DummyMethodReflection($methodName);
    }
}
