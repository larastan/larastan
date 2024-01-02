<?php

namespace Model;

use App\User;

use function PHPStan\Testing\assertType;

function testCreateOrFirst()
{
    assertType('App\User', User::createOrFirst([]));
}
