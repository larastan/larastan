<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use Larastan\Larastan\Reflection\ReflectionHelper;
use Larastan\Larastan\Reflection\StaticMethodReflection;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;
use Throwable;

use function assert;
use function class_exists;
use function sprintf;
use function strrpos;
use function substr;

/** @internal */
final class FacadesMethodsExtension implements MethodsClassReflectionExtension
{
    /** @var array<string, MethodReflection> */
    private array $cache = [];

    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (! $classReflection->is(Facade::class)) {
            return false;
        }

        if (ReflectionHelper::hasMethodTag($classReflection, $methodName)) {
            return false;
        }

        $key = $classReflection->getName() . '-' . $methodName;

        if (isset($this->cache[$key])) {
            return true;
        }

        $facadeClass = $classReflection->getName();

        $concrete = null;

        try {
            $concrete = $facadeClass::getFacadeRoot();
        } catch (Throwable) {
        }

        if ($concrete !== null) {
            $concreteClass = $concrete::class;

            if ($this->reflectionProvider->hasClass($concreteClass)) {
                $concreteReflection = $this->reflectionProvider->getClass($concreteClass);

                if ($concreteReflection->hasMethod($methodName)) {
                    $this->cache[$key] = new StaticMethodReflection(
                        $concreteReflection->getMethod($methodName, new OutOfClassScope()),
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

                if ($fakeReflection->hasMethod($methodName)) {
                    $this->cache[$key] = new StaticMethodReflection(
                        $fakeReflection->getMethod($methodName, new OutOfClassScope()),
                    );

                    return true;
                }
            }
        }

        return false;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return $this->cache[$classReflection->getName() . '-' . $methodName];
    }

    private function getFake(string $facade): string
    {
        $shortClassName = substr($facade, strrpos($facade, '\\') + 1);

        return sprintf('\\Illuminate\\Support\\Testing\\Fakes\\%sFake', $shortClassName);
    }
}
