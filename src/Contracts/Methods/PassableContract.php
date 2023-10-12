<?php
/*
 * This file is part of PhpStorm.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NunoMaduro\Larastan\Contracts\Methods;

use Illuminate\Contracts\Container\Container as ContainerContract;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Php\PhpMethodReflectionFactory;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @internal
 */
interface PassableContract
{
    public function setContainer(ContainerContract $container): void;

    public function getClassReflection(): ClassReflection;

    public function setClassReflection(ClassReflection $classReflection): PassableContract;

    public function getMethodName(): string;

    public function hasFound(): bool;

    public function searchOn(string $class): bool;

    /**
     * @throws \LogicException
     */
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

    /**
     * @param  class-string  $class
     * @param  bool  $staticAllowed
     */
    public function sendToPipeline(string $class, $staticAllowed = false): bool;

    public function getReflectionProvider(): ReflectionProvider;

    public function getMethodReflectionFactory(): PhpMethodReflectionFactory;
}
