<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Methods;

use NunoMaduro\Larastan\Concerns;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\Php\PhpMethodReflectionFactory;

/**
 * @internal
 */
final class Extension implements MethodsClassReflectionExtension, BrokerAwareExtension
{
    use Concerns\HasBroker;

    /**
     * @var \NunoMaduro\Larastan\Methods\Kernel
     */
    private $kernel;

    /**
     * Extension constructor.
     *
     * @param \PHPStan\Reflection\Php\PhpMethodReflectionFactory $methodReflectionFactory
     * @param \NunoMaduro\Larastan\Methods\Kernel|null $kernel
     */
    public function __construct(PhpMethodReflectionFactory $methodReflectionFactory, Kernel $kernel = null)
    {
        $this->kernel = $kernel ?? new Kernel($methodReflectionFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return $this->kernel->handle($this->broker, $classReflection, $methodName)
            ->hasFound();
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return $this->kernel->handle($this->broker, $classReflection, $methodName)
            ->getMethodReflection();
    }
}
