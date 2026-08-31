<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use Larastan\Larastan\Internal\RecursionGuard;
use Larastan\Larastan\Reflection\ReflectionHelper;
use Larastan\Larastan\Reflection\StaticMethodReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;
use Throwable;

use function array_key_exists;
use function assert;
use function class_exists;
use function sprintf;
use function strrpos;
use function substr;

/** @internal */
final class FacadesMethodsExtension implements MethodsClassReflectionExtension
{
    /** @var array<string, MethodReflection|false> */
    private array $cache = [];

    /** @var array<class-string, object|null> */
    private array $facadeRoots = [];

    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (! $classReflection->is(Facade::class)) {
            return false;
        }

        $key = $classReflection->getName() . '-' . $methodName;

        if (isset($this->cache[$key])) {
            return $this->cache[$key] !== false;
        }

        $result = RecursionGuard::run($key, function () use ($classReflection, $methodName, $key) {
            if (ReflectionHelper::hasMethodTag($classReflection, $methodName)) {
                return false;
            }

            $facadeClass = $classReflection->getName();

            $concrete = $this->getFacadeRoot($facadeClass);

            if ($concrete !== null) {
                $concreteClass = $concrete::class;

                if ($this->reflectionProvider->hasClass($concreteClass)) {
                    $concreteReflection = $this->reflectionProvider->getClass($concreteClass);

                    // Use hasNativeMethod() instead of hasMethod() to avoid
                    // re-entering registered MethodsClassReflectionExtensions
                    // (including this one), which would cause infinite recursion.
                    if ($concreteReflection->hasNativeMethod($methodName)) {
                        $this->cache[$key] = new StaticMethodReflection(
                            $concreteReflection->getNativeMethod($methodName),
                        );

                        return true;
                    }
                }
            }

            if (Str::startsWith($methodName, 'assert')) {
                $fakeFacadeClass = $this->getFake($facadeClass);

                if ($this->reflectionProvider->hasClass($fakeFacadeClass)) {
                    assert(class_exists($fakeFacadeClass));
                    $fakeReflection = $this->reflectionProvider->getClass($fakeFacadeClass);

                    if ($fakeReflection->hasNativeMethod($methodName)) {
                        $this->cache[$key] = new StaticMethodReflection(
                            $fakeReflection->getNativeMethod($methodName),
                        );

                        return true;
                    }
                }
            }

            return false;
        });

        if ($result === false) {
            $this->cache[$key] = false;
        }

        return $result ?? false;
    }

    /** @param class-string $facadeClass */
    private function getFacadeRoot(string $facadeClass): object|null
    {
        if (array_key_exists($facadeClass, $this->facadeRoots)) {
            return $this->facadeRoots[$facadeClass];
        }

        $concrete = null;

        try {
            $concrete = $facadeClass::getFacadeRoot();
        } catch (Throwable) {
        }

        return $this->facadeRoots[$facadeClass] = $concrete;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        $method = $this->cache[$classReflection->getName() . '-' . $methodName];

        assert($method !== false);

        return $method;
    }

    private function getFake(string $facade): string
    {
        $shortClassName = substr($facade, strrpos($facade, '\\') + 1);

        return sprintf('\\Illuminate\\Support\\Testing\\Fakes\\%sFake', $shortClassName);
    }
}
