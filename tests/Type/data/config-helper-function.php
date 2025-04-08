<?php

namespace ConfigHelperFunction;

use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('mixed', config('foo'));
    assertType('array{guard: \'web\', passwords: \'users\'}', config('auth.defaults'));

    assertType('array{1, 2, 3}', config('test.bar'));
    assertType("'bar'", config('test.foo'));
}
