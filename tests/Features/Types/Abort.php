<?php

declare(strict_types=1);

namespace Tests\Features\Types;

class Abort
{
    /**
     * Tests whether we can detect that abort terminates the application.
     *
     * @param  int  $value
     * @return string
     */
    public function testAbort(int $value): string
    {
        if ($value > 0) {
            $operator = '+';
        } elseif ($value < 0) {
            $operator = '-';
        } else {
            abort(422);
        }

        // Should not complain about $operator possibly not being defined
        return $operator;
    }
}
