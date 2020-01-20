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
use Illuminate\Database\Query\Builder as QueryBuilder;
use NunoMaduro\Larastan\Concerns\HasBroker;
use NunoMaduro\Larastan\Reflection\EloquentBuilderMethodReflection;
use NunoMaduro\Larastan\Reflection\RelationClassReflection;
use NunoMaduro\Larastan\Types\RelationType;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\DummyMethodReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ObjectType;

final class RelationForwardsCallsExtension implements MethodsClassReflectionExtension, BrokerAwareExtension
{
    use HasBroker;

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return $classReflection instanceof RelationClassReflection;
    }

    /**
     * @param RelationClassReflection $classReflection
     * @param string          $methodName
     *
     * @return MethodReflection
     * @throws \PHPStan\ShouldNotHappenException
     */
    public function getMethod(
        $classReflection,
        string $methodName
    ): MethodReflection {
        if (! ($classReflection instanceof RelationClassReflection)) {
            return new DummyMethodReflection($methodName);
        }

        $returnMethodReflection = $this->getMethodReflectionFromBuilder($methodName, $classReflection);

        if ($returnMethodReflection !== null) {
            return $returnMethodReflection;
        }

        return new DummyMethodReflection($methodName);
    }

    /**
     * @param string                  $methodName
     * @param RelationClassReflection $classReflection
     *
     * @return EloquentBuilderMethodReflection|null
     * @throws \PHPStan\ShouldNotHappenException
     */
    private function getMethodReflectionFromBuilder(string $methodName, $classReflection): ?EloquentBuilderMethodReflection
    {
        $builderHelper = new BuilderHelper($this->getBroker());
        $methodReflection = $builderHelper->searchOnEloquentBuilder($methodName, $classReflection->getRelatedModel());
        if ($methodReflection === null) {
            $methodReflection = $builderHelper->searchOnQueryBuilder($methodName, $classReflection->getRelatedModel());
        }

        if ($methodReflection !== null) {
            $parametersAcceptor = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());
            $returnType = $parametersAcceptor->getReturnType();

            if (count(array_intersect([EloquentBuilder::class, QueryBuilder::class], $returnType->getReferencedClasses())) > 0) {
                $returnType = new RelationType($classReflection->getName(), $classReflection->getRelatedModel());
            }

            return new EloquentBuilderMethodReflection(
                $methodName, $methodReflection->getDeclaringClass(),
                $methodReflection->getVariants()[0]->getParameters(),
                $returnType,
                $methodReflection->getVariants()[0]->isVariadic()
            );
        }

        if ($returnMethodReflection = $builderHelper->dynamicWhere($methodName, new ObjectType($classReflection->getName()))) {
            return $returnMethodReflection;
        }

        return null;
    }
}
