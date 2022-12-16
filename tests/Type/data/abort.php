<?php

namespace AbortTests;

use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

class Abort
{
    public function testAbort(int $value): void
    {
        if ($value > 0) {
            $operator = '+';
        } elseif ($value < 0) {
            $operator = '-';
        } else {
            abort(422);
        }

        // Should not complain about $operator possibly not being defined
        assertVariableCertainty(
            \PHPStan\TrinaryLogic::createYes(),
            $operator
        );
    }

    public function testAbortIf(?int $foo): void
    {
        abort_if(! $foo, 500);

        assertType('int<min, -1>|int<1, max>', $foo);
    }

    public function testAbortIfWithTypeCheck(int|string $foo = 5): void
    {
        abort_if(is_string($foo), 500);

        assertType('int', $foo);
    }

    public function testAbortUnless(?int $foo): void
    {
        abort_unless(! is_null($foo), 500);

        assertType('int', $foo);
    }

    public function testAbortUnlessWithTypeCheck(int|string $foo = 5): void
    {
        abort_unless(! is_string($foo), 500);

        assertType('int', $foo);
    }
}
