<?php

declare(strict_types=1);

namespace RelationExistenceColumnSelection;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @param Builder<User>         $builder
 * @param Collection<int, User> $users
 */
function constrained(User $user, Builder $builder, Collection $users): void
{
    // Laravel keeps a relation name verbatim when it is paired with a callback.
    User::with(['accounts:id,user_id' => static function () {}]);
    $builder->with(['accounts:id,user_id' => static fn ($query) => $query]);
    $builder->with(['accounts.transactions:id' => static function () {}]);
    $builder->with(['accounts:id.transactions' => static function () {}]);
    $builder->with(['accounts' => ['transactions:id' => static function () {}]]);
    $builder->with('accounts:id,user_id', static function () {});
    $builder->with(callback: static function () {}, relations: 'accounts:id,user_id');
    $builder->withOnly(['accounts:id,user_id' => static function () {}]);
    $user->load(['accounts:id,user_id' => static function () {}]);
    $user->loadMissing(['accounts:id,user_id' => static function () {}]);
    $users->load(['accounts:id,user_id' => static function () {}]);
    $users->loadMissing(['accounts:id,user_id' => static function () {}]);
    $user->accounts()->with(['transactions:id' => static function () {}]);
    $builder->withWhereHas('accounts:id,user_id', static function () {});
    $builder->withWhereHas(callback: static function () {}, relation: 'accounts:id,user_id');
    $builder->withCount(['accounts:id' => static function () {}]);
}

/**
 * @param Builder<User>         $builder
 * @param Collection<int, User> $users
 */
function parsed(User $user, Builder $builder, Collection $users, ?\Closure $callback): void
{
    User::with('accounts:id,user_id');
    User::with(['accounts:id,user_id', 'group:id']);
    $builder->with('accounts:id,user_id', 'group:id');
    $builder->with(['accounts:id' => ['transactions:id']]);
    $builder->with(['accounts:id' => ['transactions' => static function () {}]]);
    $builder->with(['accounts' => static function () {}, 'group:id']);
    $builder->withOnly(['accounts.transactions:id']);
    $user->load('accounts:id,user_id', 'group:id');
    $user->loadMissing(['accounts.transactions:id']);
    $users->load(['accounts:id,user_id']);
    $builder->withWhereHas('accounts:id,user_id');
    $builder->withWhereHas('accounts:id,user_id', null);
    $builder->withWhereHas('accounts:id,user_id', $callback);
    $builder->withCount('accounts:id');
}
