<?php

namespace ModelWithRelationsIntegration;

use App\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;

function callbacks(User $user, string $dynamic): void
{
    User::with(['accounts' => function (HasMany $relation): void {}]);
    $user->with(['group' => function (BelongsTo $relation): void {}]);
    User::with([
        'accounts' => function (HasMany $relation): void {},
        'group' => function (BelongsTo $relation): void {},
    ]);
    User::with(['accounts' => function (Relation $relation): void {}]);
    User::with([$dynamic => function (Relation $relation): void {}]);

    // A benevolent union intentionally does not retain key-to-callback correlation.
    User::with([
        'accounts' => function (BelongsTo $relation): void {},
        'group' => function (HasMany $relation): void {},
    ]);

    User::with([$dynamic => function (HasMany $relation): void {}]);
    User::with(['accounts' => function (BelongsTo $relation): void {}]);
    User::with(['accounts' => function (int $relation): void {}]);
    User::with(['missing' => function ($relation): void {}]);
    User::with(['accounts' => function (HasMany $relation): void {}, $dynamic => function ($relation): void {}]);

    acceptsAccounts($user->accounts());
    acceptsAccounts($user->group());
}

/** @param relation-of<User, 'accounts'> $relation */
function acceptsAccounts(Relation $relation): void
{
}
