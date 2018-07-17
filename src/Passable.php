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

use Mockery;
use LogicException;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use Illuminate\Contracts\Pipeline\Pipeline;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Reflection\Php\PhpMethodReflectionFactory;

/**
 * @internal
 */
final class Passable
{
    use Concerns\HasContainer;

    /**
     * @var \PHPStan\Reflection\Php\PhpMethodReflectionFactory
     */
    private $methodReflectionFactory;

    /**
     * @var \PHPStan\Broker\Broker
     */
    private $broker;

    /**
     * @var \Illuminate\Contracts\Pipeline\Pipeline
     */
    private $pipeline;

    /**
     * @var \PHPStan\Reflection\ClassReflection
     */
    private $classReflection;

    /**
     * @var string
     */
    private $methodName;

    /**
     * @var \PHPStan\Reflection\MethodReflection|null
     */
    private $methodReflection;

    /**
     * @var bool
     */
    private $staticAllowed = false;

    /**
     * Method constructor.
     *
     * @param \PHPStan\Reflection\Php\PhpMethodReflectionFactory $methodReflectionFactory
     * @param \PHPStan\Broker\Broker $broker
     * @param \Illuminate\Contracts\Pipeline\Pipeline $pipeline
     * @param \PHPStan\Reflection\ClassReflection $classReflection
     * @param string $methodName
     */
    public function __construct(
        PhpMethodReflectionFactory $methodReflectionFactory,
        Broker $broker,
        Pipeline $pipeline,
        ClassReflection $classReflection,
        string $methodName
    ) {
        $this->methodReflectionFactory = $methodReflectionFactory;
        $this->broker = $broker;
        $this->pipeline = $pipeline;
        $this->classReflection = $classReflection;
        $this->methodName = $methodName;
    }

    /**
     * @return \PHPStan\Reflection\ClassReflection
     */
    public function getClassReflection(): ClassReflection
    {
        return $this->classReflection;
    }

    /**
     * @param \PHPStan\Reflection\ClassReflection $classReflection
     *
     * @return \NunoMaduro\Larastan\Passable
     */
    public function setClassReflection(ClassReflection $classReflection): Passable
    {
        $this->classReflection = $classReflection;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethodName(): string
    {
        return $this->methodName;
    }

    /**
     * @return bool
     */
    public function hasFound(): bool
    {
        return $this->methodReflection !== null;
    }

    /**
     * @param  string $class
     *
     * @return bool
     */
    public function searchOn(string $class): bool
    {
        $classReflection = $this->broker->getClass($class);

        $found = $classReflection->hasNativeMethod($this->methodName);

        if ($found) {
            $this->setMethodReflection($classReflection->getNativeMethod($this->methodName));
        }

        return $found;
    }

    /**
     * @return \PHPStan\Reflection\MethodReflection
     *
     * @throws \LogicException
     */
    public function getMethodReflection(): MethodReflection
    {
        if ($this->methodReflection === null) {
            throw new LogicException("MethodReflection doesn't exist");
        }

        return $this->methodReflection;
    }

    /**
     * @param \PHPStan\Reflection\MethodReflection $methodReflection
     */
    public function setMethodReflection(MethodReflection $methodReflection): void
    {
        $this->methodReflection = $methodReflection;
    }

    /**
     * Declares that the provided method can be called statically.
     *
     * @param $staticAllowed
     *
     * @return void
     */
    public function setStaticAllowed($staticAllowed): void
    {
        $this->staticAllowed = $staticAllowed;
    }

    /**
     * Returns whether the method can be called statically.
     *
     * @return bool
     */
    public function isStaticAllowed(): bool
    {
        return $this->staticAllowed;
    }

    /**
     * @param string $class
     * @param bool $staticAllowed
     *
     * @return bool
     */
    public function sendToPipeline(string $class, $staticAllowed = false): bool
    {
        $classReflection = $this->broker->getClass($class);

        if (! $this->staticAllowed && $staticAllowed === false) {

            $statics = $this->resolve('config')->get('larastan.statics');
            foreach ($statics as $staticClass) {
                if ($staticClass === $class || $classReflection->isSubclassOf($staticClass)) {
                    $staticAllowed = true;
                    break;
                }
            }
        }

        $this->setStaticAllowed($this->staticAllowed ?: $staticAllowed);

        $originalClassReflection = $this->classReflection;
        $this->pipeline->send($this->setClassReflection($classReflection))
            ->then(
                function (Passable $passable) use ($originalClassReflection) {
                    if ($passable->hasFound()) {
                        $this->setMethodReflection($passable->getMethodReflection());
                        $this->setStaticAllowed($passable->isStaticAllowed());
                    }

                    $this->setClassReflection($originalClassReflection);
                }
            );

        if ($result = $this->hasFound()) {
            $methodReflection = $this->getMethodReflection();
            if (get_class($methodReflection) === PhpMethodReflection::class) {
                $methodReflection = Mockery::mock($methodReflection);
                $methodReflection->shouldReceive('isStatic')
                    ->andReturn(true);
            }

            $this->setMethodReflection($methodReflection);
        }

        return $result;
    }

    /**
     * @return \PHPStan\Broker\Broker
     */
    public function getBroker(): Broker
    {
        return $this->broker;
    }

    /**
     * @return \PHPStan\Reflection\Php\PhpMethodReflectionFactory
     */
    public function getMethodReflectionFactory(): PhpMethodReflectionFactory
    {
        return $this->methodReflectionFactory;
    }
}
