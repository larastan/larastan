<?php

namespace OptionalMethods;

use Illuminate\Support\Optional;

use function PHPStan\Testing\assertType;

class Foo
{
    public function doFoo(int $count = 1): int
    {
        return $count;
    }

    protected function doProtected(): int
    {
        return 1;
    }
}

/**
 * @param Optional<Foo>      $object
 * @param Optional<Foo|null> $nullableObject
 * @param Optional<null>     $null
 * @param Optional<string>   $string
 * @param Optional<mixed>    $mixed
 */
function test(Optional $object, Optional $nullableObject, Optional $null, Optional $string, Optional $mixed, Optional $unknown): void
{
    assertType('Illuminate\Support\Optional<OptionalMethods\Foo>', new Optional(new Foo()));

    // The call is forwarded to the wrapped object.
    assertType('int', $object->doFoo());
    assertType('int', (new Optional(new Foo()))->doFoo(5));
    assertType('*ERROR*', $object->doesNotExist());
    assertType('*ERROR*', $object->doProtected());

    // Without an object nothing is called and the result is null.
    assertType('int|null', $nullableObject->doFoo());
    assertType('null', $null->anything());
    assertType('null', $string->anything(1, 2));

    assertType('mixed', $mixed->anything());
    assertType('mixed', $unknown->anything());

    // Macros take precedence.
    assertType('bool', $object->optionalMacro());
    assertType('bool', $null->optionalMacro());
}
