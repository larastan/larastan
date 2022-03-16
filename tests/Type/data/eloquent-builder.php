<?php

declare(strict_types=1);

namespace EloquentBuilder;

use App\Post;
use App\PostBuilder;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use function PHPStan\Testing\assertType;

User::query()->has('accounts', '=', 1, 'and', function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

Post::query()->has('users', '=', 1, 'and', function (PostBuilder $query) {
    assertType('App\PostBuilder<App\Post>', $query);
});

User::query()->doesntHave('accounts', 'and', function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

Post::query()->doesntHave('users', 'and', function (PostBuilder $query) {
    assertType('App\PostBuilder<App\Post>', $query);
});

User::query()->where(function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

Post::query()->where(function (PostBuilder $query) {
    assertType('App\PostBuilder<App\Post>', $query);
});

User::query()->orWhere(function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

Post::query()->orWhere(function (PostBuilder $query) {
    assertType('App\PostBuilder<App\Post>', $query);
});

User::query()->whereHas('foo', function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

Post::query()->whereHas('foo', function (PostBuilder $query) {
    assertType('App\PostBuilder<App\Post>', $query);
});

User::query()->orWhereHas('foo', function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

Post::query()->orWhereHas('foo', function (PostBuilder $query) {
    assertType('App\PostBuilder<App\Post>', $query);
});

User::query()->hasMorph('accounts', [], '=', 1, 'and', function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

Post::query()->hasMorph('users', [], '=', 1, 'and', function (PostBuilder $query) {
    assertType('App\PostBuilder<App\Post>', $query);
});

User::query()->doesntHaveMorph('accounts', [], 'and', function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

Post::query()->doesntHaveMorph('users', [], 'and', function (PostBuilder $query) {
    assertType('App\PostBuilder<App\Post>', $query);
});

User::query()->whereHasMorph('accounts', [], function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

Post::query()->whereHasMorph('users', [], function (PostBuilder $query) {
    assertType('App\PostBuilder<App\Post>', $query);
});

User::query()->orWhereHasMorph('accounts', [], function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

Post::query()->orWhereHasMorph('users', [], function (PostBuilder $query) {
    assertType('App\PostBuilder<App\Post>', $query);
});

User::query()->whereDoesntHaveMorph('accounts', [], function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

Post::query()->whereDoesntHaveMorph('users', [], function (PostBuilder $query) {
    assertType('App\PostBuilder<App\Post>', $query);
});

User::query()->orWhereDoesntHaveMorph('accounts', [], function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

Post::query()->orWhereDoesntHaveMorph('users', [], function (PostBuilder $query) {
    assertType('App\PostBuilder<App\Post>', $query);
});

User::query()->whereDoesntHave('accounts', function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

Post::query()->whereDoesntHave('users', function (PostBuilder $query) {
    assertType('App\PostBuilder<App\Post>', $query);
});

User::query()->orWhereDoesntHave('accounts', function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

Post::query()->orWhereDoesntHave('users', function (PostBuilder $query) {
    assertType('App\PostBuilder<App\Post>', $query);
});

User::query()->firstWhere(function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

Post::query()->firstWhere(function (PostBuilder $query) {
    assertType('App\PostBuilder<App\Post>', $query);
});
