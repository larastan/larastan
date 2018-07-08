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

namespace NunoMaduro\Larastan;

use Illuminate\Database\Eloquent\Model;
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
     * Whether the methods can be accessed statically.
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

        if ($this->subjectInstanceOf($classReflection, $methodName)) {
            foreach ($this->mixins($classReflection, $methodName) as $toBeSearchClass) {
                $hasMethod = $this->broker->getClass($toBeSearchClass)
                    ->hasNativeMethod($methodName);

                if ($hasMethod) {
                    $this->pushToCache($classReflection, $methodName, $toBeSearchClass);
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

    /**
     * @param \PHPStan\Reflection\ClassReflection $classReflection
     * @param string $methodName
     *
     * @return bool
     */
    protected function subjectInstanceOf(ClassReflection $classReflection, string $methodName): bool
    {
        foreach ($this->subjects($classReflection, $methodName) as $subject) {
            return $classReflection->getName() === $subject || $classReflection->isSubclassOf($subject);
        }

        return false;
    }

    /**
     * @param \PHPStan\Reflection\ClassReflection $classReflection
     * @param string $methodName
     * @param string $toBeSearchClass
     */
    protected function pushToCache(ClassReflection $classReflection, string $methodName, string $toBeSearchClass): void
    {
        if (! array_key_exists($classReflection->getName(), $this->cache)) {
            $this->cache[$classReflection->getName()] = [];
        }

        $this->cache[$classReflection->getName()][$methodName] = $toBeSearchClass;
    }

    /**
     * Returns the class under analyse.
     *
     * @param  \PHPStan\Reflection\ClassReflection $classReflection
     * @param  string $methodName
     *
     * @return array
     */
    abstract protected function subjects(ClassReflection $classReflection, string $methodName): array;

    /**
     * Returns the classes where the native method should be search for.
     *
     * @param \PHPStan\Reflection\ClassReflection $classReflection
     * @param string $methodName
     *
     * @return array
     */
    abstract protected function mixins(ClassReflection $classReflection, string $methodName): array;
}
