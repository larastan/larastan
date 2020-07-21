<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Concerns;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;

/**
 * @internal
 */
trait HasContainer
{
    /**
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    public function setContainer(ContainerContract $container): void
    {
        $this->container = $container;
    }

    /**
     * Returns the current broker.
     */
    public function getContainer(): ContainerContract
    {
        return $this->container ?? Container::getInstance();
    }

    /**
     * Resolve the given type from the container.
     *
     * @return mixed
     */
    public function resolve(string $abstract)
    {
        $concrete = null;

        try {
            $concrete = $this->getContainer()
                ->make($abstract);
        } catch (ReflectionException $exception) {
            // ..
        } catch (BindingResolutionException $exception) {
            // ..
        } catch (NotFoundExceptionInterface $exception) {
            // ..
        }

        return $concrete;
    }
}
