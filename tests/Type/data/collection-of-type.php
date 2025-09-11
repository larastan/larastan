<?php

namespace CollectionOfType;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use function PHPStan\Testing\assertType;

/**
 * @param collection-of<\App\User> $param
 * @param collection-of<\App\Role> $param2
 * @param collection-of<\App\User|\App\Role> $union
 */
function test($param, Collection $param2, Collection $union): void
{
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $param);
    assertType('App\RoleCollection<int, App\Role>', $param2);
    assertType('App\RoleCollection<int, App\Role>|Illuminate\Database\Eloquent\Collection<int, App\User>', $union);

    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', genericMethod(\App\User::class));
    assertType('App\RoleCollection<int, App\Role>', genericMethod(\App\Role::class));
}

/**
 * @template T of Model
 *
 * @param class-string<T> $class
 *
 * @return collection-of<T>
 */
function genericMethod(string $class): Collection
{
    return (new $class)->newCollection();
}
