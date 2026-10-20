<?php

namespace ModelIncrementEachL1338;

use App\User;

use function PHPStan\Testing\assertType;

function test(User $user): void
{
    assertType('int|false', $user->incrementEach(['counter' => 1]));
    assertType('int|false', $user->decrementEach(['counter' => 1]));
}
