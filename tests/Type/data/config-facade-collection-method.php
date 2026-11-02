<?php

namespace ConfigFacadeCollectionMethod;

use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Config;
use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('*ERROR*', Config::collection('foo'));
    assertType("Illuminate\Support\Collection<'guard'|'passwords', 'users'|'web'>", Config::collection('auth.defaults'));

    assertType('Illuminate\Support\Collection<0|1|2, 1|2|3>', Config::collection('test.bar'));
    assertType('*ERROR*', Config::collection('test.foo'));
}
