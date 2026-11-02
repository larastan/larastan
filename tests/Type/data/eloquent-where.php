<?php

declare(strict_types=1);

namespace EloquentWhere;

use App\Post;
use App\User;

use function PHPStan\Testing\assertType;

function callbacks(User $user, string|null $operator, mixed $unknown): void
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::query()->where(function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    }));

    User::query()->where(function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    }, null, 2);

    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::query()->where(function ($query) {
        assertType('Illuminate\Database\Query\Builder', $query);
    }, '=', 2));

    User::where(function ($query) {
        assertType('Illuminate\Database\Query\Builder', $query);
    }, 2);

    $user->where(function ($query) {
        assertType('Illuminate\Database\Query\Builder', $query);
    }, '=', null);

    assertType('App\User|null', User::query()->firstWhere(function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    }));

    assertType('App\User|null', User::firstWhere(function ($query) {
        assertType('Illuminate\Database\Query\Builder', $query);
    }, '=', 2));

    User::query()->whereNot(function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    }, null, null, 'or');

    User::query()->whereNot(function ($query) {
        assertType('Illuminate\Database\Query\Builder', $query);
    }, '=', 2);

    User::query()->orWhereNot(function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    }, null);

    User::query()->orWhereNot(function ($query) {
        assertType('Illuminate\Database\Query\Builder', $query);
    }, '=', 2);

    Post::where(function ($query) {
        assertType('App\PostBuilder<App\Post>', $query);
    });

    Post::query()->where(function ($query) {
        assertType('Illuminate\Database\Query\Builder', $query);
    }, '=', 2);

    $user->posts()->where(function ($query) {
        assertType('App\PostBuilder<App\Post>', $query);
    });

    $user->posts()->where(function ($query) {
        assertType('Illuminate\Database\Query\Builder', $query);
    }, '=', 2);

    User::query()->where(value: 2, column: function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::query()->where(operator: '=', value: 2, column: function ($query) {
        assertType('Illuminate\Database\Query\Builder', $query);
    });

    User::query()->where(function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>|Illuminate\Database\Query\Builder', $query);
    }, $operator, 2);

    User::query()->where(function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>|Illuminate\Database\Query\Builder', $query);
    }, $unknown, 2);

    User::query()->orWhere(function ($query) {
        assertType('(Illuminate\Database\Eloquent\Builder<App\User>|Illuminate\Database\Query\Builder)', $query);
    });

    User::query()->orWhere(function ($query) {
        assertType('(Illuminate\Database\Eloquent\Builder<App\User>|Illuminate\Database\Query\Builder)', $query);
    }, null);

    User::query()->orWhere(function ($query) {
        assertType('(Illuminate\Database\Eloquent\Builder<App\User>|Illuminate\Database\Query\Builder)', $query);
    }, null, null);

    Post::orWhere(function ($query) {
        assertType('(App\PostBuilder<App\Post>|Illuminate\Database\Query\Builder)', $query);
    }, '=', 2);
}
