<?php

namespace ModelRelations;

use App\User;

use function PHPStan\Testing\assertType;

function test(User $user): void
{
    assertType('App\Account', $user->accounts()->createOrFirst([]));
}
