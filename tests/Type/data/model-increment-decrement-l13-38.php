<?php

namespace ModelIncrementDecrementL1338;

use App\Account;
use App\User;

use function PHPStan\Testing\assertType;

function test(User $user, User|Account $userOrAccount): void
{
    assertType('int|false', $user->increment('counter'));
    assertType('int|false', $user->decrement('counter'));
    assertType('int|false', $userOrAccount->increment('counter'));
    assertType('int|false', $userOrAccount->decrement('counter'));
}
