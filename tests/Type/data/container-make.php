<?php

namespace ContainerMake;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use function PHPStan\Testing\assertType;

function doFoo(\Illuminate\Container\Container $container, \Illuminate\Contracts\Container\Container $container2)
{
    assertType('Illuminate\Config\Repository', $container->make(Repository::class));
    assertType('Illuminate\Config\Repository', $container->makeWith(Repository::class));
    assertType('Illuminate\Config\Repository', $container->resolve(Repository::class));

    assertType('Illuminate\Config\Repository', $container2->make(Repository::class));
    assertType('Illuminate\Config\Repository', $container2->makeWith(Repository::class));
    assertType('Illuminate\Config\Repository', $container2->resolve(Repository::class));
}

/** @param class-string<Model> $foo */
function doBar(string $foo, \Illuminate\Contracts\Container\Container $container)
{
    assertType('mixed', $container->make($foo));
    assertType('mixed', $container->makeWith($foo));
    assertType('mixed', $container->resolve($foo));
}
