<?php

declare(strict_types=1);

namespace Tests\Features\Types;

use Illuminate\Validation\ValidationException;

class ThrowIf
{
    public function testThrowIfTryCatch(?int $foo): ?int
    {
        try {
            throw_if(! $foo, ValidationException::withMessages(['$foo is null']));
        } catch (\Exception $e) {
        }

        // Should still be nullable because we don't know if the exception was caught.
        return $foo;
    }

    /**
     * @param  int|string  $foo
     * @return int
     */
    public function testThrowIfWithTypeCheck($foo = 5): int
    {
        throw_if(is_string($foo), new \Exception('$foo is a string'));

        return $foo;
    }
}
