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

use Mockery;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\MethodsClassReflectionExtension;

/**
 * @internal
 */
abstract class AbstractExtension implements MethodsClassReflectionExtension, BrokerAwareExtension
{
    use Concerns\HasBroker;

    /**
     * Contains the Class Reflection.
     *
     * @var \PHPStan\Reflection\ClassReflection
     */
    protected $classReflection;

    /**
     * Returns the class under analyse.
     *
     * @return string
     */
    abstract protected function subject(): string;

    /**
     * Returns the class where the native method should be search for.
     *
     * @return string
     */
    abstract protected function searchIn(): string;

    /**
     * Whether the methods can be accessed statically
     */
    protected $staticAccess = false;

    /**
     * {@inheritdoc}
     */
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        $this->classReflection = $classReflection;

        return $classReflection->isSubclassOf($this->subject()) && $this->broker->getClass($this->searchIn())
                ->hasNativeMethod($methodName);
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        $this->classReflection = $classReflection;

        $methodReflection = $this->broker->getClass($this->searchIn())
            ->getNativeMethod($methodName);

        if ($this->staticAccess) {
            $methodReflection = Mockery::mock($methodReflection);
            $methodReflection->shouldReceive('isStatic')
                ->andReturn(true);
        }

        return $methodReflection;
    }
}
