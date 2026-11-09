<?php

namespace EloquentBuilderPaginate;

use App\User;
use Illuminate\Pagination\LengthAwarePaginator;

/** @return LengthAwarePaginator<int, User> */
function perPageCallback(): LengthAwarePaginator
{
    return User::query()->paginate(fn (int $total): int => $total > 1000 ? 50 : 15);
}

/** @return LengthAwarePaginator<int, User> */
function namedPerPageCallbackWithTotal(): LengthAwarePaginator
{
    return User::query()->paginate(perPage: fn (int $total): ?int => $total > 1000 ? 50 : null, total: 1200);
}

/** @return LengthAwarePaginator<int, User> */
function forwardedPerPageCallback(): LengthAwarePaginator
{
    return User::paginate(static fn ($total) => 15);
}

/** @return LengthAwarePaginator<int, \App\Account> */
function relationPerPageCallback(User $user): LengthAwarePaginator
{
    return $user->accounts()->paginate(fn (int $total): int => 15);
}

function invalidPerPageCallback(): void
{
    User::query()->paginate(fn (string $total): int => 15);
    User::query()->paginate(fn (int $total): string => '15');
}
