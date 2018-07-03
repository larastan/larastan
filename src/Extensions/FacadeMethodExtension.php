<?php

declare(strict_types=1);

/**
 * This file is part of Laravel Code Analyse.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace NunoMaduro\LaravelCodeAnalyse\Extensions;

use PHPStan\Broker\Broker;
use Illuminate\Support\Facades\Facade;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use NunoMaduro\LaravelCodeAnalyse\FacadeConcreteClassResolver;

final class FacadeMethodExtension implements MethodsClassReflectionExtension, BrokerAwareExtension
{

    /**
     * @var \PHPStan\Broker\Broker
     */
    private $broker;

    /**
     * @param \PHPStan\Broker\Broker $broker
     */
    public function setBroker(Broker $broker): void
    {
        $this->broker = $broker;
    }

    /**
     * {@inheritdoc}
     */
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->isSubclassOf(Facade::class)) {
            $facadeClass = $classReflection->getNativeReflection()
                ->getName();

            if ($concrete = $facadeClass::getFacadeRoot()) {
                return $this->broker->getClass($concrete)
                    ->hasNativeMethod($methodName);
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        $facadeClass = $classReflection->getNativeReflection()
            ->getName();

        return $this->broker->getClass($facadeClass::getFacadeRoot())
            ->getNativeMethod($methodName);
    }
}
