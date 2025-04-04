<?php

namespace Arrayable;

use App\ValueObjects\Foo;
use App\ValueObjects\FooList;
use function PHPStan\Testing\assertType;

function test(): void
{
    $foo = new Foo;
    assertType('array<string, int>', $foo->toArray());

    $fooList = new FooList;
    $array = $fooList->toArray();
    assertType('array<int, Illuminate\Contracts\Support\Arrayable<string, int>>', $array);
    $fooFromArray = $array[0];
    assertType('Illuminate\Contracts\Support\Arrayable<string, int>', $fooFromArray);
    assertType('array<string, int>', $fooFromArray->toArray());
}
