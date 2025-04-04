<?php

namespace Arrayable;

use App\ValueObjects\Foo;
use function PHPStan\Testing\assertType;

function test(): void
{
    $foo = new Foo;
    assertType('array<string, int>', $foo->toArray());
}
