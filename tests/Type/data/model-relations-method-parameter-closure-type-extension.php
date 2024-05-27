<?php

namespace ModelRelationsMethodParameterClosureTypeExtension;

use App\Comment;
use App\Post;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use function PHPStan\Testing\assertType;

function test(): void
{
    User::has('accounts', '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    User::has('teams', '=', 1, 'and', function (Builder $query) {
        assertType('App\ChildTeamBuilder', $query);
    });

    User::whereHas('accounts.group', function (Builder $qu) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Group>', $qu);
    });

    User::has('accounts.group', callback: function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Group>', $query);
    });

    Post::has('users', '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    Post::has('users.teams', '=', 1, 'and', function (Builder $query) {
        assertType('App\ChildTeamBuilder', $query);
    });

    User::doesntHave('accounts', 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::doesntHave('users', 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::whereHas('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::whereHas('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::orWhereHas('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::orWhereHas('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::hasMorph('accounts', [], '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::hasMorph('users', [], '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::doesntHaveMorph('accounts', [], 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::doesntHaveMorph('users', [], 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::whereHasMorph('accounts', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::whereHasMorph('users', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::orWhereHasMorph('accounts', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::orWhereHasMorph('users', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::whereDoesntHaveMorph('accounts', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::whereDoesntHaveMorph('users', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::orWhereDoesntHaveMorph('accounts', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::orWhereDoesntHaveMorph('users', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::whereDoesntHave('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::whereDoesntHave('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::orWhereDoesntHave('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::orWhereDoesntHave('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    Comment::whereDoesntHaveMorph('commentable', Post::class, function (Builder $query, string $type) {
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
