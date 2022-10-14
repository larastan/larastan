<?php

namespace OptionalType;

use function optional;
use function PHPStan\Testing\assertType;
use StdClass;

assertType('mixed', optional(['foo' => 'bar']));

assertType('int', optional('1', function (string $value): int {
    return 1;
}));

assertType('null', optional(null, function (string $value): int {
    return 1;
}));

assertType('mixed', optional(new StdClass()));
assertType('mixed', optional(new StdClass())->foo);

class Foo
{
    public function doFoo(): void
    {
    }
}

assertType('mixed', optional(new Foo)->doFoo());
