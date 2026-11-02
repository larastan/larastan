<?php

namespace ConfigHelperFunction;

use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('mixed', config('foo'));
    assertType('array{guard: \'web\', passwords: \'users\'}', config('auth.defaults'));

    assertType('array{1, 2, 3}', config('test.bar'));
    assertType("'bar'", config('test.foo'));
    assertType('mixed', config('test.foo.bar'));
    assertType('mixed', config('auth.defaults.missing'));
    assertType('string', config('test.default'));

    assertType("'bar'|int", config('test.foo', 5));
    assertType('int', config('foo', 5));
}
