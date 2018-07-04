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
     * Returns the class under analyse.
     *
     * @return string
     */
    abstract protected function subject(): string;

    /**
     * Returns the classes where the native method should be search for.
     *
     * @param \PHPStan\Reflection\ClassReflection $classReflection
     *
     * @return array
     */
    abstract protected function searchIn(ClassReflection $classReflection): array;

    /**
     * Whether the methods can be accessed statically
     */
    protected $staticAccess = false;

    /**
     * Holds already discovered methods.
     *
     * @var array
     */
    private $cache = [];

    /**
     * {@inheritdoc}
     */
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        $hasMethod = false;

        if ($classReflection->getName() === $this->subject() || $classReflection->isSubclassOf($this->subject())) {
            foreach ($this->searchIn($classReflection) as $toBeSearchClass) {
                $hasMethod = $this->broker->getClass($toBeSearchClass)
                    ->hasNativeMethod($methodName);

                if ($hasMethod) {

                    if (! array_key_exists($classReflection->getName(), $this->cache)) {
                        $this->cache[$classReflection->getName()] = [];
                    }

                    $this->cache[$classReflection->getName()][$methodName] = $toBeSearchClass;
                    break;
                }
            }
        }

        return $hasMethod;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        $methodReflection = $this->broker->getClass($this->cache[$classReflection->getName()][$methodName])
            ->getNativeMethod($methodName);

        if ($this->staticAccess) {
            $methodReflection = Mockery::mock($methodReflection);
            $methodReflection->shouldReceive('isStatic')
                ->andReturn(true);
        }

        return $methodReflection;
    }
}
