<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Illuminate\Contracts\Pipeline\Pipeline;
use Larastan\Larastan\Concerns;
use Larastan\Larastan\Contracts\Methods\PassableContract;
use Larastan\Larastan\Reflection\StaticMethodReflection;
use LogicException;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Php\PhpMethodReflectionFactory;
use PHPStan\Reflection\ReflectionProvider;

/** @internal */
final class Passable implements PassableContract
{
    use Concerns\HasContainer;

    private MethodReflection|null $methodReflection = null;

    private bool $staticAllowed = false;

    public function __construct(
        private PhpMethodReflectionFactory $methodReflectionFactory,
        private ReflectionProvider $reflectionProvider,
        private Pipeline $pipeline,
        private ClassReflection $classReflection,
        private string $methodName,
    ) {
    }

    public function getClassReflection(): ClassReflection
    {
        return $this->classReflection;
    }

    public function setClassReflection(ClassReflection $classReflection): PassableContract
    {
        $this->classReflection = $classReflection;

        return $this;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function hasFound(): bool
    {
        return $this->methodReflection !== null;
    }

    public function searchOn(string $class): bool
    {
        $classReflection = $this->reflectionProvider->getClass($class);

        $found = $classReflection->hasNativeMethod($this->methodName);

        if ($found) {
            $this->setMethodReflection($classReflection->getNativeMethod($this->methodName));
        }

        return $found;
    }

    public function getMethodReflection(): MethodReflection
    {
        if ($this->methodReflection === null) {
            throw new LogicException("MethodReflection doesn't exist");
        }

        return $this->methodReflection;
    }

    public function setMethodReflection(MethodReflection $methodReflection): void
    {
        $this->methodReflection = $methodReflection;
    }

    public function setStaticAllowed(bool $staticAllowed): void
    {
        $this->staticAllowed = $staticAllowed;
    }

    public function isStaticAllowed(): bool
    {
        return $this->staticAllowed;
    }

    public function sendToPipeline(string $class, bool $staticAllowed = false): bool
    {
        $classReflection = $this->reflectionProvider->getClass($class);

        $this->setStaticAllowed($this->staticAllowed ?: $staticAllowed);

        $originalClassReflection = $this->classReflection;
        $this->pipeline->send($this->setClassReflection($classReflection))
            ->then(
                function (PassableContract $passable) use ($originalClassReflection): void {
                    if ($passable->hasFound()) {
                        $this->setMethodReflection($passable->getMethodReflection());
                        $this->setStaticAllowed($passable->isStaticAllowed());
                    }

                    $this->setClassReflection($originalClassReflection);
                },
            );

        $result = $this->hasFound();

        if ($result) {
            $this->setMethodReflection(new StaticMethodReflection($this->getMethodReflection()));
        }

        return $result;
    }

    public function getReflectionProvider(): ReflectionProvider
    {
        return $this->reflectionProvider;
    }

    public function getMethodReflectionFactory(): PhpMethodReflectionFactory
    {
        return $this->methodReflectionFactory;
    }
}
