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

use PHPStan\Type\Type;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\IntegerType;
use NunoMaduro\Larastan\Concerns;
use PHPStan\Type\IntersectionType;
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

    /** @var string[] */
    private $modelRetrievalMethods = ['find', 'findMany', 'findOrFail'];

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
        $isPublic = true;
        $returnType = new ObjectType(Builder::class);
        $methodReflection = $this->getBuilderReflection()->getNativeMethod($methodName);

        if (in_array($methodName, ['increment', 'decrement'], true)) {
            $methodReflection = $this->broker->getClass(Model::class)->getNativeMethod($methodName);

            $returnType = new IntegerType();
        }

        if (in_array($methodName, ['paginate', 'simplePaginate'], true)) {
            $methodReflection = $this->broker->getClass(QueryBuilder::class)->getNativeMethod($methodName);

            $returnType = new ObjectType($methodName === 'paginate' ? LengthAwarePaginator::class : Paginator::class);
        }

        if (in_array($methodName, $this->modelRetrievalMethods, true)) {
            $methodReflection = $this->getBuilderReflection()->getNativeMethod($methodName);

            $returnType = $this->getReturnTypeOfModelRetrievalMethod($methodName, $classReflection->getName());
        }

        return new EloquentBuilderMethodReflection(
            $methodName, $classReflection,
            ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getParameters(),
            $isPublic, $returnType
        );
    }

    private function getReturnTypeOfModelRetrievalMethod(string $methodName, string $className) : Type
    {
        return [
            'find' => new IntersectionType([
                new ObjectType($className), new ObjectType(\Illuminate\Database\Eloquent\Collection::class), new NullType()
            ]),
            'findMany' => new ObjectType(\Illuminate\Database\Eloquent\Collection::class),
            'findOrFail' => new IntersectionType([
                new ObjectType($className), new ObjectType(\Illuminate\Database\Eloquent\Collection::class), new NullType()
            ]),
        ][$methodName];
    }
}
