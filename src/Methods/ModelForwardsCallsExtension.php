<?php

declare(strict_types=1);

/**
 * This file is part of Larastan.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace NunoMaduro\Larastan\Methods;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use NunoMaduro\Larastan\Concerns;
use NunoMaduro\Larastan\Reflection\EloquentBuilderMethodReflection;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\DummyMethodReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ParametersAcceptorSelector;
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

        return $this->getBuilderReflection()->hasNativeMethod($methodName) || $this->broker->getClass(QueryBuilder::class)->hasNativeMethod($methodName);
    }

    /**
     * @param ClassReflection $originalModelReflection
     * @param string          $methodName
     *
     * @return MethodReflection
     * @throws ShouldNotHappenException
     */
    public function getMethod(ClassReflection $originalModelReflection, string $methodName): MethodReflection
    {
        $returnMethodReflection = $this->getMethodReflectionFromBuilder($methodName, $originalModelReflection->getName());

        if ($returnMethodReflection !== null) {
            return $returnMethodReflection;
        }

        return new DummyMethodReflection($methodName);
    }

    /**
     * @throws ShouldNotHappenException
     */
    private function getMethodReflectionFromBuilder(string $methodName, string $modelName): ?EloquentBuilderMethodReflection
    {
        $builderHelper = new BuilderHelper($this->getBroker());
        $methodReflection = $builderHelper->searchOnEloquentBuilder($methodName, $modelName);
        if ($methodReflection === null) {
            $methodReflection = $builderHelper->searchOnQueryBuilder($methodName, $modelName);
        }

        if ($methodReflection !== null) {
            $parametersAcceptor = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());
            $returnType = $parametersAcceptor->getReturnType();

            if (count(array_intersect([EloquentBuilder::class, QueryBuilder::class], $returnType->getReferencedClasses())) > 0) {
                $returnType = new GenericObjectType(EloquentBuilder::class, [new ObjectType($modelName)]);
            }

            return new EloquentBuilderMethodReflection(
                $methodName, $this->getBroker()->getClass($modelName),
                $parametersAcceptor->getParameters(),
                $returnType,
                $parametersAcceptor->isVariadic()
            );
        }

        return $builderHelper->dynamicWhere($methodName, new GenericObjectType(EloquentBuilder::class, [new ObjectType($modelName)]));
    }
}
