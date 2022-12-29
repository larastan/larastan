<?php

namespace ContainerMake;

use Illuminate\Contracts\Config\Repository;
use function PHPStan\Testing\assertType;

/** @var \Illuminate\Container\Container $container */
assertType('Illuminate\Contracts\Config\Repository', $container->make(Repository::class));
assertType('Illuminate\Contracts\Config\Repository', $container->makeWith(Repository::class));
assertType('Illuminate\Contracts\Config\Repository', $container->resolve(Repository::class));

/** @var \Illuminate\Contracts\Container\Container $container */
assertType('Illuminate\Contracts\Config\Repository', $container->make(Repository::class));
assertType('Illuminate\Contracts\Config\Repository', $container->makeWith(Repository::class));
assertType('Illuminate\Contracts\Config\Repository', $container->resolve(Repository::class));
