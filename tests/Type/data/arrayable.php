<?php

namespace Arrayable;

use Illuminate\Contracts\Support\Arrayable;
use function PHPStan\Testing\assertType;

function test(Foo $foo): void
{
    assertType('array<string, int>', $foo->toArray());
}

/** @implements Arrayable<string, int>*/
class Foo implements Arrayable
{
    public function toArray()
    {
        return [];
    }
}
