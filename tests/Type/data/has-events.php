<?php

namespace Helpers;

use App\Account;

use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('array<string, class-string>', (new Account)->dispatchesEvents());
}
