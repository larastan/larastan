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

use NunoMaduro\Larastan\Concerns\HasBroker;
use NunoMaduro\Larastan\Reflection\RelationClassReflection;
use NunoMaduro\Larastan\Types\RelationType;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\DummyMethodReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;

final class RelationForwardsCallsExtension implements MethodsClassReflectionExtension, BrokerAwareExtension
{
    use HasBroker;

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return $classReflection instanceof RelationClassReflection;
    }

    public function getMethod(
        ClassReflection $classReflection,
        string $methodName
    ): MethodReflection {
        if (! ($classReflection instanceof RelationClassReflection)) {
            return new DummyMethodReflection($methodName);
        }

        $builderHelper = new BuilderHelper($this->getBroker());
        $returnMethodReflection = $builderHelper->getMethodReflectionFromBuilder(
            $classReflection,
            $methodName,
            $classReflection->getRelatedModel(),
            new RelationType($classReflection->getName(), $classReflection->getRelatedModel())
        );

        if ($returnMethodReflection !== null) {
            return $returnMethodReflection;
        }

        return new DummyMethodReflection($methodName);
    }
}
