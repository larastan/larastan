<?php

declare(strict_types=1);

namespace EloquentBuilder;

use App\Post;
use App\PostBuilder;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use function PHPStan\Testing\assertType;

User::query()->has('accounts', '=', 1, 'and', function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder', $query);
    //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
});

Post::query()->has('users', '=', 1, 'and', function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder', $query);
    //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

User::query()->doesntHave('accounts', 'and', function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder', $query);
    //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
});

Post::query()->doesntHave('users', 'and', function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder', $query);
    //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

User::query()->whereHas('accounts', function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder', $query);
    //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
});

Post::query()->whereHas('users', function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder', $query);
    //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

User::query()->orWhereHas('accounts', function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder', $query);
    //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
});

Post::query()->orWhereHas('users', function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder', $query);
    //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

User::query()->hasMorph('accounts', [], '=', 1, 'and', function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder', $query);
    //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
});

Post::query()->hasMorph('users', [], '=', 1, 'and', function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder', $query);
    //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

User::query()->doesntHaveMorph('accounts', [], 'and', function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder', $query);
    //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
});

Post::query()->doesntHaveMorph('users', [], 'and', function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder', $query);
    //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

User::query()->whereHasMorph('accounts', [], function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder', $query);
    //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
});

Post::query()->whereHasMorph('users', [], function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder', $query);
    //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

User::query()->orWhereHasMorph('accounts', [], function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder', $query);
    //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
});

Post::query()->orWhereHasMorph('users', [], function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder', $query);
    //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

User::query()->whereDoesntHaveMorph('accounts', [], function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder', $query);
    //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
});

Post::query()->whereDoesntHaveMorph('users', [], function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder', $query);
    //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

User::query()->orWhereDoesntHaveMorph('accounts', [], function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder', $query);
    //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
});

Post::query()->orWhereDoesntHaveMorph('users', [], function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder', $query);
    //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

User::query()->whereDoesntHave('accounts', function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder', $query);
    //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
});

Post::query()->whereDoesntHave('users', function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder', $query);
    //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

User::query()->orWhereDoesntHave('accounts', function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder', $query);
    //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
});

Post::query()->orWhereDoesntHave('users', function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder', $query);
    //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

User::query()->firstWhere(function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});

Post::query()->firstWhere(function (PostBuilder $query) {
    assertType('App\PostBuilder<App\Post>', $query);
});

Post::query()->where(static function (PostBuilder $query) {
    assertType('App\PostBuilder<App\Post>', $query
        ->orWhere('bar', 'LIKE', '%foo%')
        ->orWhereRelation('users', 'name', 'LIKE', '%foo%'));
});
