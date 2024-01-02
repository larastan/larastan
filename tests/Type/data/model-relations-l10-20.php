<?php

namespace ModelRelations;

use App\Account;
use App\User;

use function PHPStan\Testing\assertType;

function test(User $user, \App\Address $address, Account $account, ExtendsModelWithPropertyAnnotations $model, Tag $tag, User|Account $union)
{
    assertType('App\Account', $user->accounts()->createOrFirst([]));
}
