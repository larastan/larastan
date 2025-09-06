<?php

namespace EloquentBuilderParameterExtension;

use App\Post;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use function PHPStan\Testing\assertType;

function test(): void
{
    User::query()->with(['accounts' => ['transactions', 'group'], 'posts']);
    User::with(['accounts' => ['transactions', 'group'], 'posts']);

    User::query()->with(['accounts' => function (HasMany $query) {
        assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', $query);
    }, 'posts']);

    User::withWhereHas('accounts', function (Builder|HasMany $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>|Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', $query);
    });

    User::query()->withWhereHas('accounts', function (Builder|HasMany $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>|Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', $query);
    });

    User::query()->with(['accounts.posts' => function (BelongsToMany $query) {
        assertType('Illuminate\Database\Eloquent\Relations\BelongsToMany<App\Post, App\Account, Illuminate\Database\Eloquent\Relations\Pivot, \'pivot\'>', $query);
    }]);

    User::query()->with(['accounts' => function ($query) {
        assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', $query);
    }, 'posts']);

    User::query()->with(['accounts.posts' => function ($query) {
        assertType('Illuminate\Database\Eloquent\Relations\BelongsToMany<App\Post, App\Account, Illuminate\Database\Eloquent\Relations\Pivot, \'pivot\'>', $query);
    }]);

    User::with(['accounts' => function (HasMany $query) {
        assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', $query);
    }, 'posts']);

    User::with(['accounts.posts' => function (BelongsToMany $query) {
        assertType('Illuminate\Database\Eloquent\Relations\BelongsToMany<App\Post, App\Account, Illuminate\Database\Eloquent\Relations\Pivot, \'pivot\'>', $query);
    }]);

    User::query()->has('accounts', '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    User::query()->has('posts', '=', 1, 'and', function (Builder $query) {
        assertType('App\PostBuilder<App\Post>', $query);
    });

    Post::query()->has('users', '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::query()->doesntHave('accounts', 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::query()->doesntHave('users', 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::query()->whereHas('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::query()->whereHas('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::query()->withWhereHas('accounts', function (Builder|HasMany $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>|Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', $query);
    });

    Post::query()->withWhereHas('users', function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>|Illuminate\Database\Eloquent\Relations\BelongsToMany<App\User, App\Post, Illuminate\Database\Eloquent\Relations\Pivot, \'pivot\'>', $query);
    });

    User::query()->orWhereHas('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::query()->orWhereHas('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::query()->hasMorph('accounts', [], '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::query()->hasMorph('users', [], '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::query()->doesntHaveMorph('accounts', [], 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::query()->doesntHaveMorph('users', [], 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::query()->whereHasMorph('accounts', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::query()->whereHasMorph('users', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::query()->orWhereHasMorph('accounts', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::query()->orWhereHasMorph('users', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::query()->whereDoesntHaveMorph('accounts', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::query()->whereDoesntHaveMorph('users', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::query()->orWhereDoesntHaveMorph('accounts', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::query()->orWhereDoesntHaveMorph('users', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::query()->whereDoesntHave('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::query()->whereDoesntHave('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::query()->orWhereDoesntHave('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::query()->orWhereDoesntHave('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });
}
