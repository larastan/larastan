<?php

namespace ModelIncrementDecrementPreL1338;

use App\Account;
use App\User;

use function PHPStan\Testing\assertType;

function test(User $user, User|Account $userOrAccount): void
{
    assertType('int', $user->increment('counter'));
    assertType('int', $user->decrement('counter'));
    assertType('int', $userOrAccount->increment('counter'));
    assertType('int', $userOrAccount->decrement('counter'));
}
