<?php

namespace ConfigRepositoryMethod;

use Illuminate\Config\Repository;
use function PHPStan\Testing\assertType;

function test(Repository $config): void
{
    assertType('*ERROR*', $config->collection('foo'));
    assertType("Illuminate\Support\Collection<'guard'|'passwords', 'users'|'web'>", $config->collection('auth.defaults'));

    assertType('Illuminate\Support\Collection<0|1|2, 1|2|3>', $config->collection('test.bar'));
    assertType('*ERROR*', $config->collection('test.foo'));
}
