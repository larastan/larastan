<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Illuminate\Database\Eloquent\Model;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\Php\PhpMethodReflectionFactory;
use PHPStan\Reflection\ReflectionProvider;

use function array_key_exists;

/** @internal */
final class Extension implements MethodsClassReflectionExtension
{
    private Kernel $kernel;

    /** @var MethodReflection[] */
    private array $methodReflections = [];

    public function __construct(PhpMethodReflectionFactory $methodReflectionFactory, ReflectionProvider $reflectionProvider, Kernel|null $kernel = null)
    {
        $this->kernel = $kernel ?? new Kernel($methodReflectionFactory, $reflectionProvider);
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->getName() === Model::class) {
            return false;
        }

        if (array_key_exists($methodName . '-' . $classReflection->getName(), $this->methodReflections)) {
            return true;
        }

        $passable = $this->kernel->handle($classReflection, $methodName);

        $found = $passable->hasFound();

        if ($found) {
            $this->methodReflections[$methodName . '-' . $classReflection->getName()] = $passable->getMethodReflection();
        }

        return $found;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return $this->methodReflections[$methodName . '-' . $classReflection->getName()];
    }
}
