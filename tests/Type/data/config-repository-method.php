<?php

namespace ConfigRepositoryMethod;

use Illuminate\Config\Repository;
use function PHPStan\Testing\assertType;

function test(Repository $config): void
{
    assertType('mixed', $config->get('foo'));
    assertType('array{guard: \'web\', passwords: \'users\'}', $config->get('auth.defaults'));

    assertType('array{1, 2, 3}', $config->get('test.bar'));
    assertType("'bar'", $config->get('test.foo'));
    assertType("'bar'|int", $config->get('test.foo', 5));

    assertType('*ERROR*', $config->collection('foo'));
    assertType("Illuminate\Support\Collection<'guard'|'passwords', 'users'|'web'>", $config->collection('auth.defaults'));
    assertType('Illuminate\Support\Collection<0|1|2, 1|2|3>', $config->collection('test.bar'));
    assertType('*ERROR*', $config->collection('test.foo'));
}
