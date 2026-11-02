<?php

namespace ModelIncrementEachL1320;

use App\User;

use function PHPStan\Testing\assertType;

function test(User $user): void
{
    assertType('int', $user->incrementEachQuietly(['counter' => 1]));
    assertType('int', $user->decrementEachQuietly(['counter' => 1]));
}
