<?php

declare(strict_types=1);

/**
 * This file is part of Larastan.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace NunoMaduro\Larastan;

use PHPStan\Broker\Broker;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Container\Container;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Php\PhpMethodReflectionFactory;
use Illuminate\Contracts\Container\Container as ContainerContract;

/**
 * @internal
 */
final class Kernel
{
    /**
     * @var \PHPStan\Reflection\Php\PhpMethodReflectionFactory
     */
    private $methodReflectionFactory;

    /**
     * @var \Illuminate\Contracts\Container\Container|null
     */
    private $container;

    /**
     * Kernel constructor.
     *
     * @param \PHPStan\Reflection\Php\PhpMethodReflectionFactory $methodReflectionFactory
     * @param \Illuminate\Contracts\Container\Container|null $container
     */
    public function __construct(
        PhpMethodReflectionFactory $methodReflectionFactory,
        ContainerContract $container = null
    ) {
        $this->methodReflectionFactory = $methodReflectionFactory;
        $this->container = $container ?? Container::getInstance();
    }

    /**
     * @param \PHPStan\Broker\Broker $broker
     * @param \PHPStan\Reflection\ClassReflection $classReflection
     * @param string $methodName
     *
     * @return \NunoMaduro\Larastan\Passable
     */
    public function handle(Broker $broker, ClassReflection $classReflection, string $methodName): Passable
    {
        $pipeline = new Pipeline($this->container);

        $passable = new Passable($this->methodReflectionFactory, $broker, $pipeline, $classReflection, $methodName);

        $pipeline->send($passable)
            ->through(
                [
                    Middlewares\SelfClass::class,
                    Middlewares\Macros::class,
                    Middlewares\Mixins::class,
                    Middlewares\Contracts::class,
                    Middlewares\Facades::class,
                    Middlewares\Managers::class,
                    Middlewares\Auths::class,
                    Middlewares\ModelScopes::class,
                    Middlewares\RedirectResponseWiths::class,
                ]
            )
            ->then(
                function ($method) {
                }
            );

        return $passable;
    }
}
