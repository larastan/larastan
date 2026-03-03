<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Illuminate\Support\Str;
use Larastan\Larastan\Concerns;
use Larastan\Larastan\Reflection\StaticMethodReflection;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;

/** @internal */
final class ContractsMethodsExtension implements MethodsClassReflectionExtension
{
    use Concerns\HasContainer;

    /** @var array<string, MethodReflection> */
    private array $cache = [];

    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (! $classReflection->isInterface() || ! Str::startsWith($classReflection->getName(), 'Illuminate\Contracts')) {
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

        $concreteClass = $concrete::class;

        if (! $this->reflectionProvider->hasClass($concreteClass)) {
            return false;
        }

        $concreteReflection = $this->reflectionProvider->getClass($concreteClass);

        if (! $concreteReflection->hasMethod($methodName)) {
            return false;
        }

        $this->cache[$key] = new StaticMethodReflection(
            $concreteReflection->getMethod($methodName, new OutOfClassScope()),
        );

        return true;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return $this->cache[$classReflection->getName() . '-' . $methodName];
    }
}
