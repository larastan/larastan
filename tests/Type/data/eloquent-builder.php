<?php

declare(strict_types=1);

namespace EloquentBuilder;

use App\Post;
use App\PostBuilder;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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

function doFoo(User $user, Post $post): void
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $user->newQuery());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $user->newModelQuery());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $user->newQueryWithoutRelationships());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $user->newQueryWithoutScopes());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $user->newQueryWithoutScope('foo'));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $user->newQueryForRestoration([1]));

    assertType('App\PostBuilder<App\Post>', $post->newQuery());
    assertType('App\PostBuilder<App\Post>', $post->newModelQuery());
    assertType('App\PostBuilder<App\Post>', $post->newQueryWithoutRelationships());
    assertType('App\PostBuilder<App\Post>', $post->newQueryWithoutScopes());
    assertType('App\PostBuilder<App\Post>', $post->newQueryWithoutScope('foo'));
    assertType('App\PostBuilder<App\Post>', $post->newQueryForRestoration([1]));

    assertType('Illuminate\Support\LazyCollection<int, App\User>', User::query()->lazy());
    assertType('Illuminate\Support\LazyCollection<int, App\User>', User::query()->lazyById());
    assertType('Illuminate\Support\LazyCollection<int, App\User>', User::query()->lazyByIdDesc());
    assertType('Illuminate\Support\LazyCollection<int, App\Post>', $post->newQuery()->lazy());
    assertType('Illuminate\Support\LazyCollection<int, App\Post>', $post->newQuery()->lazyById());
    assertType('Illuminate\Support\LazyCollection<int, App\Post>', $post->newQuery()->lazyByIdDesc());
};

class Foo extends Model
{
    /** @phpstan-use FooTrait<Foo> */
    use FooTrait;
}

/** @template TModelClass of Model */
trait FooTrait
{
    /** @return Builder<TModelClass> */
    public function doFoo(): Builder
    {
        return $this->newQuery();
    }
}
