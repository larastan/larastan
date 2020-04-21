<?php

declare(strict_types=1);

namespace Tests\Features\Types;

use Illuminate\Validation\ValidationException;

class ThrowUnless
{
    public function testThrowUnless(?int $foo): int
    {
        throw_unless(! is_null($foo), ValidationException::withMessages(['$foo is null']));

        return $foo;
    }

    /**
     * @param int|string $foo
     * @return int
     */
    public function testThrowUnlessWithTypeCheck($foo = 5): int
    {
        throw_unless(! is_string($foo), new \Exception('$foo is a string'));

        return $foo;
    }
}
