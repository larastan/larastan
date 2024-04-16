<?php

namespace Model;

use App\User;

use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('App\User', User::createOrFirst([]));
}
