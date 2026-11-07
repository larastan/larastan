<?php

namespace ApplicationMake;

use FailsAtRuntime;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;

use function PHPStan\Testing\assertType;

/** @param class-string<Model> $model */
function test(Application $app, ApplicationContract $app2, string $model, mixed $mixed): void
{
    assertType('Illuminate\Config\Repository', $app->make(Repository::class));
    assertType('Illuminate\Config\Repository', $app->makeWith(Repository::class));
    assertType('Illuminate\Config\Repository', $app->resolve(Repository::class));

    assertType('Illuminate\Config\Repository', $app2->make(Repository::class));
    assertType('Illuminate\Config\Repository', $app2->makeWith(Repository::class));
    assertType('Illuminate\Config\Repository', $app2->resolve(Repository::class));

    assertType('Illuminate\Database\Eloquent\Model', $app->make($model));
    assertType('Illuminate\Database\Eloquent\Model', $app->makeWith($model));
    assertType('Illuminate\Database\Eloquent\Model', $app->resolve($model));

    assertType('Illuminate\Database\Eloquent\Model', $app2->make($model));
    assertType('Illuminate\Database\Eloquent\Model', $app2->makeWith($model));
    assertType('Illuminate\Database\Eloquent\Model', $app2->resolve($model));

    assertType('FailsAtRuntime', $app->resolve(FailsAtRuntime::class));
    assertType('FailsAtRuntime', $app2->resolve(FailsAtRuntime::class));

    assertType('mixed', $app->make($mixed));
    assertType('mixed', $app->makeWith($mixed));
    assertType('mixed', $app->resolve($mixed));

    assertType('mixed', $app2->make($mixed));
    assertType('mixed', $app2->makeWith($mixed));
    assertType('mixed', $app2->resolve($mixed));
}
