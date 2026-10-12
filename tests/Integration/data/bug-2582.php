<?php

namespace Bug2582;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * @param Builder<User>         $builder
 * @param Collection<int, User> $users
 */
function test(Builder $builder, User $user, Collection $users): void
{
    User::with(['accounts' => ['transactions', 'group' => ['users']]]);
    User::with(['accounts' => ['group' => fn (Relation $relation) => $relation->latest()]]);
    $builder->with(['accounts' => ['group' => ['users']]]);
    $builder->withOnly(['accounts' => ['group' => fn (Relation $relation) => $relation->latest()]]);
    $user->load(['accounts' => ['group' => ['users']]]);
    $user->loadMissing(['accounts' => ['group' => ['users']]]);
    $users->load(['accounts' => ['group' => ['users']]]);
    $users->loadMissing(['accounts' => ['group' => ['users']]]);
}
