<?php

namespace ApplicationMake;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use function PHPStan\Testing\assertType;

function doFoo(\Illuminate\Foundation\Application $app, \Illuminate\Contracts\Foundation\Application $app2)
{
    assertType('Illuminate\Config\Repository', $app->make(Repository::class));
    assertType('Illuminate\Config\Repository', $app->makeWith(Repository::class));
    assertType('Illuminate\Config\Repository', $app->resolve(Repository::class));

    assertType('Illuminate\Config\Repository', $app2->make(Repository::class));
    assertType('Illuminate\Config\Repository', $app2->makeWith(Repository::class));
    assertType('Illuminate\Config\Repository', $app2->resolve(Repository::class));
}

/** @param class-string<Model> $foo */
function doBar(string $foo, \Illuminate\Foundation\Application $app)
{
    assertType('mixed', $app->make($foo));
    assertType('mixed', $app->makeWith($foo));
    assertType('mixed', $app->resolve($foo));
}
