<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Concerns;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Throwable;

/**
 * @internal
 */
trait HasContainer
{
    /**
     * @var ?\Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function setContainer(ContainerContract $container): void
    {
        $this->container = $container;
    }

    /**
     * Returns the current broker.
     *
     * @return \Illuminate\Contracts\Container\Container
     */
    public function getContainer(): ContainerContract
    {
        return $this->container ?? Container::getInstance();
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  string  $abstract
     * @return mixed
     */
    public function resolve(string $abstract)
    {
        try {
            $concrete = $this->getContainer()->make($abstract);
        } catch (Throwable) {
            return null;
        }

        return $concrete;
    }
}
