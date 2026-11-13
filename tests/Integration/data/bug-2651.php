<?php

namespace Bug2651;

use App\User;

function test(User $user, int $count): void
{
    $user->accounts_count = 5;
    $user->accounts_count = $count;
    $user->accounts_count++;
    $user->accounts_count--;
    $user->accounts_count = 'foo';
}
