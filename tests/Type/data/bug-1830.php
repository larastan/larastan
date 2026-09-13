<?php

namespace InfiniteRecursion;

use Illuminate\Database\Eloquent\Model;

use function PHPStan\Testing\assertType;

/** @mixin User */
abstract class BaseModel extends Model { }

class User extends BaseModel { }


function test(User $user): void
{
    assertType('int<0, max>', (int) $user->id);
}
