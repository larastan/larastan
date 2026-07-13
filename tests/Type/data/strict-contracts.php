<?php

namespace StrictContracts;

use FailsAtRuntime;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\App;

use function PHPStan\Testing\assertType;

function test(
    Application $app,
    ApplicationContract $app2,
    Container $container,
    ContainerContract $container2,
): void {
    assertType('Illuminate\Contracts\Config\Repository', app(Repository::class));
    assertType('Illuminate\Contracts\Config\Repository', resolve(Repository::class));
    assertType('Illuminate\Auth\AuthManager', app('auth'));
    assertType('Illuminate\Auth\AuthManager', resolve('auth'));

    assertType('Illuminate\Contracts\Config\Repository', App::make(Repository::class));
    assertType('Illuminate\Contracts\Config\Repository', App::makeWith(Repository::class));

    assertType('Illuminate\Contracts\Config\Repository', $app->make(Repository::class));
    assertType('Illuminate\Contracts\Config\Repository', $app->makeWith(Repository::class));
    assertType('Illuminate\Contracts\Config\Repository', $app->resolve(Repository::class));
    assertType('FailsAtRuntime', $app->resolve(FailsAtRuntime::class));

    assertType('Illuminate\Contracts\Config\Repository', $app2->make(Repository::class));
    assertType('Illuminate\Contracts\Config\Repository', $app2->makeWith(Repository::class));
    assertType('Illuminate\Contracts\Config\Repository', $app2->resolve(Repository::class));
    assertType('FailsAtRuntime', $app2->resolve(FailsAtRuntime::class));

    assertType('Illuminate\Contracts\Config\Repository', $container->make(Repository::class));
    assertType('Illuminate\Contracts\Config\Repository', $container->makeWith(Repository::class));
    assertType('Illuminate\Contracts\Config\Repository', $container->resolve(Repository::class));

    assertType('Illuminate\Contracts\Config\Repository', $container2->make(Repository::class));
    assertType('Illuminate\Contracts\Config\Repository', $container2->makeWith(Repository::class));
    assertType('Illuminate\Contracts\Config\Repository', $container2->resolve(Repository::class));
}
