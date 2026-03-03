<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Illuminate\Support\Manager;
use InvalidArgumentException;
use Larastan\Larastan\Concerns;
use Larastan\Larastan\Reflection\StaticMethodReflection;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;

/** @internal */
final class ManagersMethodsExtension implements MethodsClassReflectionExtension
{
    use Concerns\HasContainer;

    /** @var array<string, MethodReflection> */
    private array $cache = [];

    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (! $classReflection->is(Manager::class) || $classReflection->isAbstract()) {
            return false;
        }

        $key = $classReflection->getName() . '-' . $methodName;

        if (isset($this->cache[$key])) {
            return true;
        }

        $concrete = $this->resolve($classReflection->getName());

        if ($concrete === null) {
            return false;
        }

        $driver = null;

        try {
            $driver = $concrete->driver();
        } catch (InvalidArgumentException) {
        }

        if ($driver === null) {
            return false;
        }

        $driverClass = $driver::class;

        if (! $this->reflectionProvider->hasClass($driverClass)) {
            return false;
        }

        $driverReflection = $this->reflectionProvider->getClass($driverClass);

        if (! $driverReflection->hasMethod($methodName)) {
            return false;
        }

        $this->cache[$key] = new StaticMethodReflection(
            $driverReflection->getMethod($methodName, new OutOfClassScope()),
        );

        return true;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return $this->cache[$classReflection->getName() . '-' . $methodName];
    }
}
