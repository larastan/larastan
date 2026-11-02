<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Auth\Guard;
use Larastan\Larastan\Concerns;
use Larastan\Larastan\Reflection\StaticMethodReflection;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;

use function in_array;

/** @internal */
final class AuthsMethodsExtension implements MethodsClassReflectionExtension
{
    use Concerns\HasContainer;
    use Concerns\LoadsAuthModel;

    /** @var array<string, MethodReflection> */
    private array $cache = [];

    /** @var string[] */
    private array $authContracts = [
        Authenticatable::class,
        CanResetPassword::class,
        Authorizable::class,
    ];

    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        $key = $classReflection->getName() . '-' . $methodName;

        if (isset($this->cache[$key])) {
            return true;
        }

        $className = $classReflection->getName();

        if (in_array($className, $this->authContracts, true)) {
            return $this->findMethodInAuthModels($key, $methodName);
        }

        if ($className === Factory::class || $className === AuthManager::class) {
            return $this->findMethodOnClass($key, Guard::class, $methodName);
        }

        return false;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return $this->cache[$classReflection->getName() . '-' . $methodName];
    }

    private function findMethodInAuthModels(string $cacheKey, string $methodName): bool
    {
        $config = $this->resolve('config');

        if ($config === null) {
            return false;
        }

        $authModels = $this->getAuthModels($config);

        foreach ($authModels as $authModel) {
            if ($this->findMethodOnClass($cacheKey, $authModel, $methodName)) {
                return true;
            }
        }

        return false;
    }

    private function findMethodOnClass(string $cacheKey, string $class, string $methodName): bool
    {
        if (! $this->reflectionProvider->hasClass($class)) {
            return false;
        }

        $classReflection = $this->reflectionProvider->getClass($class);

        if (! $classReflection->hasMethod($methodName)) {
            return false;
        }

        $this->cache[$cacheKey] = new StaticMethodReflection(
            $classReflection->getMethod($methodName, new OutOfClassScope()),
        );

        return true;
    }
}
