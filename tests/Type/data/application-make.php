<?php

namespace ApplicationMake;

use Illuminate\Contracts\Config\Repository;
use function PHPStan\Testing\assertType;

/** @var \Illuminate\Foundation\Application $app */
assertType('Illuminate\Contracts\Config\Repository', $app->make(Repository::class));
assertType('Illuminate\Contracts\Config\Repository', $app->makeWith(Repository::class));
assertType('Illuminate\Contracts\Config\Repository', $app->resolve(Repository::class));

/** @var \Illuminate\Contracts\Foundation\Application $app */
assertType('Illuminate\Contracts\Config\Repository', $app->make(Repository::class));
assertType('Illuminate\Contracts\Config\Repository', $app->makeWith(Repository::class));
assertType('Illuminate\Contracts\Config\Repository', $app->resolve(Repository::class));
