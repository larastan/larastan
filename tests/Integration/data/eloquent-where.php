<?php

declare(strict_types=1);

namespace EloquentWhereCompatibility;

use App\Post;
use App\PostBuilder;
use App\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

function callbacks(User $user): void
{
    User::query()->where(static function (QueryBuilder $query): void {
        $query->selectRaw('COUNT(*)')->from('posts');
    }, '=', 2);

    $subquery = fn (QueryBuilder $query) => $query->selectRaw('COUNT(*)')->from('posts');
    $nested = static fn (EloquentBuilder $query) => $query->whereKey(1);

    User::where($subquery, 2);
    $user->firstWhere($subquery, '=', 2);
    User::query()->whereNot($subquery, '=', 2);
    User::query()->orWhereNot($subquery, '=', 2);
    $user->posts()->where($subquery, '=', 2);
    Post::where($subquery, '=', 2);

    User::query()->where($nested);
    User::firstWhere($nested);
    User::query()->whereNot($nested, null, 2);
    User::query()->orWhereNot($nested, null);
    Post::where(static fn (PostBuilder $query) => $query->whereKey(1));

    User::query()->where(column: $subquery, operator: '=', value: 2);
    User::query()->where(column: $nested, value: 2);

    User::orWhere($nested);
    User::query()->orWhere($subquery, null);
    User::query()->orWhere($nested, null, null);
    User::query()->orWhere($subquery, '=', 2);
    Post::orWhere(static fn (PostBuilder $query) => $query->whereKey(1));

    User::query()->where($subquery);
    User::query()->firstWhere($subquery);
    User::query()->whereNot($subquery, null);
    User::query()->orWhereNot($subquery, null, null);

    User::query()->where($nested, '=', 2);
    User::query()->firstWhere($nested, '=', 2);
    User::query()->whereNot($nested, '=', 2);
    User::query()->orWhereNot($nested, '=', 2);
    User::query()->orWhere(function (int $query): void {});
}
