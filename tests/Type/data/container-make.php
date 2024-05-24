<?php

namespace ContainerMake;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Container\Container;

use function PHPStan\Testing\assertType;

/** @param class-string<Model> $model */
function test(
    Container $container,
    ContainerContract $container2,
    string $model,
): void {
    assertType('Illuminate\Config\Repository', $container->make(Repository::class));
    assertType('Illuminate\Config\Repository', $container->makeWith(Repository::class));
    assertType('Illuminate\Config\Repository', $container->resolve(Repository::class));

    assertType('Illuminate\Config\Repository', $container2->make(Repository::class));
    assertType('Illuminate\Config\Repository', $container2->makeWith(Repository::class));
    assertType('Illuminate\Config\Repository', $container2->resolve(Repository::class));

    assertType('mixed', $container->make($foo));
    assertType('mixed', $container->makeWith($foo));
    assertType('mixed', $container->resolve($foo));
}
