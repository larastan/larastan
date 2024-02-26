<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Illuminate\Pipeline\Pipeline;
use Larastan\Larastan\Concerns;
use Larastan\Larastan\Contracts\Methods\PassableContract;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Php\PhpMethodReflectionFactory;
use PHPStan\Reflection\ReflectionProvider;

/** @internal */
final class Kernel
{
    use Concerns\HasContainer;

    public function __construct(
        private PhpMethodReflectionFactory $methodReflectionFactory,
        private ReflectionProvider $reflectionProvider,
    ) {
    }

    public function handle(ClassReflection $classReflection, string $methodName): PassableContract
    {
        $pipeline = new Pipeline($this->getContainer());

        $passable = new Passable($this->methodReflectionFactory, $this->reflectionProvider, $pipeline, $classReflection, $methodName);

        $pipeline->send($passable)
            ->through(
                [
                    Pipes\SelfClass::class,
                    Pipes\Contracts::class,
                    Pipes\Facades::class,
                    Pipes\Managers::class,
                    Pipes\Auths::class,
                ],
            )
            ->then(
                static function ($method): void {
                },
            );

        return $passable;
    }
}
