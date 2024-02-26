<?php

declare(strict_types=1);

namespace Larastan\Larastan\Contracts\Methods;

use Illuminate\Contracts\Container\Container as ContainerContract;
use LogicException;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Php\PhpMethodReflectionFactory;
use PHPStan\Reflection\ReflectionProvider;

/** @internal */
interface PassableContract
{
    public function setContainer(ContainerContract $container): void;

    public function getClassReflection(): ClassReflection;

    public function setClassReflection(ClassReflection $classReflection): PassableContract;

    public function getMethodName(): string;

    public function hasFound(): bool;

    public function searchOn(string $class): bool;

    /** @throws LogicException */
    public function getMethodReflection(): MethodReflection;

    public function setMethodReflection(MethodReflection $methodReflection): void;

    /**
     * Declares that the provided method can be called statically.
     */
    public function setStaticAllowed(bool $staticAllowed): void;

    /**
     * Returns whether the method can be called statically.
     */
    public function isStaticAllowed(): bool;

    /** @param  class-string $class */
    public function sendToPipeline(string $class, bool $staticAllowed = false): bool;

    public function getReflectionProvider(): ReflectionProvider;

    public function getMethodReflectionFactory(): PhpMethodReflectionFactory;
}
