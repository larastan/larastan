<?php

namespace BuilderRelationsMethodParameterClosureTypeExtension;

use App\Comment;
use App\Post;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use function PHPStan\Testing\assertType;

function test(): void
{
    User::query()->has('accounts', '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    User::query()->has('teams', '=', 1, 'and', function (Builder $query) {
        assertType('App\ChildTeamBuilder', $query);
    });

    User::query()->whereHas('accounts.group', function (Builder $qu) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Group>', $qu);
    });

    User::query()->has('accounts.group', callback: function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Group>', $query);
    });

    Post::query()->has('users', '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    Post::query()->has('users.teams', '=', 1, 'and', function (Builder $query) {
        assertType('App\ChildTeamBuilder', $query);
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

    Comment::query()->whereDoesntHaveMorph('commentable', Post::class, function (Builder $query, string $type) {
        assertType('App\PostBuilder<App\Post>', $query);
        assertType("'App\\\Post'", $type);
    });

    User::whereRelation('posts', function(Builder $query) {
        assertType('App\PostBuilder<App\Post>', $query);
    });

    Comment::orWhereDoesntHaveMorph('commentable', [Post::class, User::class], function (Builder $query, string $type) {
        assertType('App\PostBuilder<App\Post>|Illuminate\Database\Eloquent\Builder<App\User>', $query);
        assertType("'App\\\Post'|'App\\\User'", $type);
    });

    Comment::orWhereDoesntHaveMorph('commentable', ['*'], function (Builder $query, string $type) {
        assertType('Illuminate\Database\Eloquent\Builder<*>', $query);
        assertType('string', $type);
    });
}
