<?php

namespace Bug2651;

use App\User;

function test(User $user): void
{
    $user->accounts_count = 5;
    $user->accounts_count++;
}
