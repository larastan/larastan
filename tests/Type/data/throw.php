<?php

namespace Throw;

use Illuminate\Validation\ValidationException;

use function PHPStan\Testing\assertType;

function test(?int $foo): void
{
    try {
        throw_if(! $foo, ValidationException::withMessages(['$foo is null']));

        assertType('int<min, -1>|int<1, max>', $foo);
    } catch (\Exception $e) {
    }

    assertType('int|null', $foo);

    throw_unless(! is_null($foo), ValidationException::withMessages(['$foo is null']));
    assertType('int', $foo);

    /** @var int|string $foo */
    throw_if(is_string($foo), new \Exception('$foo is a string'));
    assertType('int', $foo);

    /** @var int|string $foo */
    throw_unless(! is_string($foo), new \Exception('$foo is a string'));
    assertType('int', $foo);

    /** @var int|string $foo */
    throw_if(is_string($foo));
    assertType('int', $foo);

    /** @var int|string $foo */
    throw_if(is_string($foo), 'Exception message');
    assertType('int', $foo);

    /** @var int|string $foo */
    throw_unless(! is_string($foo));
    assertType('int', $foo);

    /** @var int|string $foo */
    throw_unless(! is_string($foo), 'Exception message');
    assertType('int', $foo);
}
