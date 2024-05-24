<?php

namespace Carbon;

use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('string', Carbon::foo());
    assertType('string', Carbon::now()->foo());
}
