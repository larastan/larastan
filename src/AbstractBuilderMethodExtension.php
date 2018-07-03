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

namespace NunoMaduro\LaravelCodeAnalyse;

use PHPStan\Broker\Broker;
use Illuminate\Database\Eloquent\Model;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\MethodsClassReflectionExtension;

abstract class AbstractBuilderMethodExtension implements MethodsClassReflectionExtension, BrokerAwareExtension
{
    /**
     * @var \PHPStan\Broker\Broker
     */
    private $broker;

    /**
     * Returns the builder class.
     *
     * @return string
     */
    abstract public function getBuilderClass(): string;

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
        return ($classReflection->isSubclassOf(Model::class) || $classReflection->getName() === Model::class) && $this->broker->getClass($this->getBuilderClass())
                ->hasNativeMethod($methodName);
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        $methodReflection = $this->broker->getClass($this->getBuilderClass())
            ->getNativeMethod($methodName);

        $mock = \Mockery::mock($methodReflection);
        $mock->shouldReceive('isStatic')
            ->andReturn(true);

        return $mock;
    }
}
