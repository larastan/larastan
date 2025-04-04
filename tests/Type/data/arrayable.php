<?php

namespace Arrayable;

use Illuminate\Contracts\Support\Arrayable;
use function PHPStan\Testing\assertType;

/**
 * @implements Arrayable<string, int>
 */
class Foo implements Arrayable
{
    public function toArray()
    {

    }
}

function test(): void
{
    $foo = new Foo;
    assertType('array<string, int>', $foo->toArray());
}
