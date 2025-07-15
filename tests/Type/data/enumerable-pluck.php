<?php

declare(strict_types=1);

namespace EloquentBuilderPluck;

use App\Post;
use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

use function PHPStan\Testing\assertType;

/**
 * @param  EloquentCollection<int, User>  $users
 * @param  Collection<int, array{user: array{id: int, name: string}}>  $arrays
 * @param  EloquentCollection<int, Post>  $posts
 * @param  LazyCollection<int, User>  $lazyUsers
 */
function test(
    EloquentCollection $users,
    Collection $arrays,
    EloquentCollection $posts,
    LazyCollection $lazyUsers,
): void {
    assertType('Illuminate\Support\Collection<int, string>', $users->pluck('name'));
    assertType('Illuminate\Support\Collection<string, string>', $users->pluck('name', 'name'));

    assertType('Illuminate\Support\Collection<int, string>', $arrays->pluck('user.name'));
    assertType('Illuminate\Support\Collection<string, string>', $arrays->pluck('user.name', 'user.name'));

    assertType('Illuminate\Support\Collection<int, string>', $users->pluck(fn ($u) => $u->name));
    assertType('Illuminate\Support\Collection<string, string>', $users->pluck(fn ($u) => $u->name, fn ($u) => $u->name));
    assertType('Illuminate\Support\Collection<string, string>', $users->pluck(function ($u) { return $u->name; }, function ($u) { return $u->name; }));

    assertType('Illuminate\Support\Collection<int, string>', $posts->pluck('user.name'));
    assertType('Illuminate\Support\Collection<string, string>', $posts->pluck('user.name', 'user.name'));
    assertType('Illuminate\Support\Collection<int, string>', $posts->pluck(['user', 'name']));
    assertType('Illuminate\Support\Collection<string, string>', $posts->pluck(['user', 'name'], ['user', 'name']));

    assertType('Illuminate\Support\LazyCollection<int, string>', $lazyUsers->pluck('name'));
    assertType('Illuminate\Support\LazyCollection<string, string>', $lazyUsers->pluck('name', 'name'));
}
