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
}
