<?php

namespace ModelIncrementEachL133;

use App\User;

use function PHPStan\Testing\assertType;

function test(User $user): void
{
    assertType('int', $user->incrementEach(['counter' => 1]));
    assertType('int', $user->decrementEach(['counter' => 1]));
}
