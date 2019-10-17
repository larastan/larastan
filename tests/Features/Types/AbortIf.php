<?php

declare(strict_types=1);

namespace Tests\Features\Types;

class AbortIf
{
    public function testAbortIf(?int $foo) : int
    {
        abort_if(! $foo, 500);

        return $foo;
    }

    /**
     * @param int|string $foo
     * @return int
     */
    public function testAbortIfWithTypeCheck($foo = 5) : int
    {
        abort_if(is_string($foo), 500);

        return $foo;
    }
}
