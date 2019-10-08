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

use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use NunoMaduro\Larastan\Concerns;
use Illuminate\Database\Eloquent\Model;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use Illuminate\Database\Eloquent\Builder;
use PHPStan\Reflection\BrokerAwareExtension;
use Illuminate\Contracts\Pagination\Paginator;
use PHPStan\Reflection\ParametersAcceptorSelector;
use Illuminate\Database\Query\Builder as QueryBuilder;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use NunoMaduro\Larastan\Reflection\EloquentBuilderMethodReflection;

final class ModelForwardsCallsExtension implements  MethodsClassReflectionExtension, BrokerAwareExtension
{
    use Concerns\HasBroker;

    /**
     * @return ClassReflection
     * @throws \PHPStan\Broker\ClassNotFoundException
     */
    protected function getBuilderReflection(): ClassReflection
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

        return $this->getBuilderReflection()->hasNativeMethod($methodName);
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        if (in_array($methodName, ['increment', 'decrement'], true)) {
            $methodReflection = $this->broker->getClass(Model::class)->getNativeMethod($methodName);

            return new EloquentBuilderMethodReflection(
                $methodName, $classReflection,
                ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getParameters(),
                true, new IntegerType()
            );
        }

        if (in_array($methodName, ['paginate', 'simplePaginate'], true)) {
            $methodReflection = $this->broker->getClass(QueryBuilder::class)->getNativeMethod($methodName);

            $returnClass = $methodName === 'paginate' ? LengthAwarePaginator::class : Paginator::class;

            return new EloquentBuilderMethodReflection(
                $methodName, $classReflection,
                ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getParameters(),
                true, new ObjectType($returnClass)
            );
        }

        $methodReflection = $this->getBuilderReflection()->getNativeMethod($methodName);

        return new EloquentBuilderMethodReflection(
            $methodName, $classReflection,
            ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getParameters()
        );
    }
}
