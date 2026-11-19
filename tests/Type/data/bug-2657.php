<?php

namespace Bug2657;

use App\Account;
use App\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

use function PHPStan\Testing\assertType;

final class RelationMacro
{
    /** @return HasMany<Account, User> */
    public function accounts(User $user): HasMany
    {
        $relation = $user->accounts()->latestFirst('created_at');

        assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', $relation);

        return $relation->orderBy('id');
    }
}
