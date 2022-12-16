<?php

namespace Throw;

use Illuminate\Validation\ValidationException;
use function PHPStan\Testing\assertType;

class ThrowTest
{
    public function testThrowIfTryCatch(?int $foo): void
    {
        try {
            throw_if(! $foo, ValidationException::withMessages(['$foo is null']));

            assertType('int<min, -1>|int<1, max>', $foo);
        } catch (\Exception $e) {
        }

        assertType('int|null', $foo);
    }

    public function testThrowIfWithTypeCheck(int|string $foo = 5): void
    {
        throw_if(is_string($foo), new \Exception('$foo is a string'));

        assertType('int', $foo);
    }

    public function testThrowUnless(?int $foo): void
    {
        throw_unless(! is_null($foo), ValidationException::withMessages(['$foo is null']));

        assertType('int', $foo);
    }

    public function testThrowUnlessWithTypeCheck(int|string $foo = 5): void
    {
        throw_unless(! is_string($foo), new \Exception('$foo is a string'));

        assertType('int', $foo);
    }
}
