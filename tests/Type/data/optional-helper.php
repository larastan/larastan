<?php

namespace OptionalType;

use StdClass;

use function optional;
use function PHPStan\Testing\assertType;

class Foo
{
    public function doFoo(): void { }
}

function test(): void
{
    assertType('mixed', optional(['foo' => 'bar']));
    assertType('int', optional('1', function (string $value): int {
        return 1;
    }));
    assertType('null', optional(null, function (string $value): int {
        return 1;
    }));
    assertType('mixed', optional(new StdClass()));
    assertType('mixed', optional(new StdClass())->foo);
    assertType('mixed', optional(new Foo)->doFoo());
}
