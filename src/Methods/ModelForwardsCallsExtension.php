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

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use NunoMaduro\Larastan\Concerns;
use NunoMaduro\Larastan\Reflection\EloquentBuilderMethodReflection;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;

final class ModelForwardsCallsExtension implements MethodsClassReflectionExtension, BrokerAwareExtension
{
    use Concerns\HasBroker;

    /** @var string[] */
    public const MODEL_RETRIEVAL_METHODS = ['first', 'find', 'findMany', 'findOrFail', 'firstOrFail'];

    /** @var string[] */
    public const MODEL_CREATION_METHODS = ['make', 'create', 'forceCreate', 'findOrNew', 'firstOrNew', 'updateOrCreate', 'fromQuery', 'firstOrCreate'];

    /**
     * @return ClassReflection
     * @throws \PHPStan\Broker\ClassNotFoundException
     */
    private function getBuilderReflection(): ClassReflection
    {
        return $this->broker->getClass(Builder::class);
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (! $classReflection->isSubclassOf(Model::class)) {
            return false;
        }

        if (in_array($methodName, ['increment', 'decrement', 'paginate', 'simplePaginate'], true)) {
            return true;
        }

        if ($classReflection->hasNativeMethod('scope'.ucfirst($methodName))) {
            // scopes handled later
            return false;
        }

        return $this->getBuilderReflection()->hasNativeMethod($methodName) || $this->broker->getClass(QueryBuilder::class)->hasNativeMethod($methodName);
    }

    /**
     * @throws \PHPStan\Broker\ClassNotFoundException
     * @throws \PHPStan\Reflection\MissingMethodFromReflectionException
     * @throws \PHPStan\ShouldNotHappenException
     */
    public function getMethod(ClassReflection $originalModelReflection, string $methodName): MethodReflection
    {
        $returnType = null;
        $methodReflection = null;
        $queryBuilderReflection = $this->broker->getClass(QueryBuilder::class);

        if (in_array($methodName, ['increment', 'decrement'], true)) {
            $methodReflection = $this->getBuilderReflection()->getNativeMethod($methodName);

            $returnType = new IntegerType();
        } elseif (in_array($methodName, ['paginate', 'simplePaginate'], true)) {
            $methodReflection = $queryBuilderReflection->getNativeMethod($methodName);

            $returnType = new ObjectType($methodName === 'paginate' ? LengthAwarePaginator::class : Paginator::class);
        } elseif (in_array($methodName, array_merge(self::MODEL_CREATION_METHODS, self::MODEL_RETRIEVAL_METHODS), true)) {
            $methodReflection = $this->getBuilderReflection()->getNativeMethod($methodName);

            $returnType = ModelTypeHelper::replaceStaticTypeWithModel($methodReflection->getVariants()[0]->getReturnType(), $originalModelReflection->getName());
        }

        if ($this->getBuilderReflection()->hasNativeMethod($methodName)) {
            $methodReflection = $methodReflection ?? $this->getBuilderReflection()->getNativeMethod($methodName);
            $parametersAcceptor = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());

            return new EloquentBuilderMethodReflection(
                $methodName, $originalModelReflection,
                $parametersAcceptor->getParameters(),
                $returnType,
                $parametersAcceptor->isVariadic()
            );
        }

        $parametersAcceptor = ParametersAcceptorSelector::selectSingle($queryBuilderReflection->getNativeMethod($methodName)->getVariants());

        return new EloquentBuilderMethodReflection(
            $methodName, $originalModelReflection,
            $parametersAcceptor->getParameters(),
            $returnType,
            $parametersAcceptor->isVariadic()
        );
    }
}
