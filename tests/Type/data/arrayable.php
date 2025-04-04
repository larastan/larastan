<?php

namespace Arrayable;

use App\ValueObjects\Foo;
use App\ValueObjects\FooList;
use function PHPStan\Testing\assertType;

function test(): void
{
    $foo = new Foo;
    assertType('array<string, mixed>', $foo->toArray());

    $fooList = new FooList;
    $array = $fooList->toArray();
    $fooFromArray = $array[0];
    assertType('array<int, Illuminate\Contracts\Support\Arrayable<string, mixed>>', $array);
    assertType('Illuminate\Contracts\Support\Arrayable<string, mixed>', $fooFromArray);
    assertType('array<string, mixed>', $fooFromArray->toArray());
}
