<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Methods;

use Illuminate\Pipeline\Pipeline;
use NunoMaduro\Larastan\Concerns;
use NunoMaduro\Larastan\Contracts\Methods\PassableContract;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Php\PhpMethodReflectionFactory;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @internal
 */
final class Kernel
{
    use Concerns\HasContainer;

    /**
     * @var \PHPStan\Reflection\Php\PhpMethodReflectionFactory
     */
    private $methodReflectionFactory;
    /**
     * @var \PHPStan\Reflection\ReflectionProvider
     */
    private $reflectionProvider;

    /**
     * Kernel constructor.
     *
     * @param \PHPStan\Reflection\Php\PhpMethodReflectionFactory $methodReflectionFactory
     */
    public function __construct(
        PhpMethodReflectionFactory $methodReflectionFactory,
        ReflectionProvider $reflectionProvider
    ) {
        $this->methodReflectionFactory = $methodReflectionFactory;
        $this->reflectionProvider = $reflectionProvider;
    }

    /**
     * @param \PHPStan\Reflection\ClassReflection $classReflection
     * @param  string  $methodName
     * @return \NunoMaduro\Larastan\Contracts\Methods\PassableContract
     */
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
                ]
            )
            ->then(
                function ($method) {
                }
            );

        return $passable;
    }
}
