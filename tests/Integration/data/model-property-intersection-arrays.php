<?php

namespace ModelPropertyIntersectionArrays;

use App\User;

/**
 * @param non-empty-array<string, mixed> $attributes
 * @param non-empty-list<string> $columns
 */
function checkUser(User $user, array $attributes, array $columns): void
{
    $user->fill($attributes);
    $user->update($attributes);
    User::query()->where($attributes)->get($columns);
    User::query()->create($attributes);
}
