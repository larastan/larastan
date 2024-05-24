<?php

namespace ApplicationMake;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;

use function PHPStan\Testing\assertType;

/** @param class-string<Model> $model */
function test(Application $app, ApplicationContract $app2, string $model): void
{
    assertType('Illuminate\Config\Repository', $app->make(Repository::class));
    assertType('Illuminate\Config\Repository', $app->makeWith(Repository::class));
    assertType('Illuminate\Config\Repository', $app->resolve(Repository::class));

    assertType('Illuminate\Config\Repository', $app2->make(Repository::class));
    assertType('Illuminate\Config\Repository', $app2->makeWith(Repository::class));
    assertType('Illuminate\Config\Repository', $app2->resolve(Repository::class));

    assertType('mixed', $app->make($model));
    assertType('mixed', $app->makeWith($model));
    assertType('mixed', $app->resolve($model));
}
