<?php

declare(strict_types=1);

namespace Tests\Features\Types;

class AbortUnless
{
    public function testAbortUnless(?int $foo): int
    {
        abort_unless(! is_null($foo), 500);

        return $foo;
    }

    /**
     * @param  int|string  $foo
     * @return int
     */
    public function testAbortUnlessWithTypeCheck($foo = 5): int
    {
        abort_unless(! is_string($foo), 500);

        return $foo;
    }
}
