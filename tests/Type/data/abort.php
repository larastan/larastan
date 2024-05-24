<?php

namespace AbortTests;

use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

function test(?int $foo): void
{
    if ($foo > 0) {
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

    abort_if(! $foo, 500);
    assertType('int<min, -1>|int<1, max>', $foo);

    /** @var int|string $foo */
    abort_if(is_string($foo), 500);
    assertType('int', $foo);

    /** @var ?int $foo */
    abort_unless(! is_null($foo), 500);
    assertType('int', $foo);

    /** @var int|string $foo */
    abort_unless(! is_string($foo), 500);
    assertType('int', $foo);
}
