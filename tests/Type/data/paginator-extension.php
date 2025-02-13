<?php

declare(strict_types=1);

namespace PaginatorExtension;

use App\User;

use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('Illuminate\Pagination\LengthAwarePaginator<int, App\User>', User::paginate());
    assertType('array<int, App\User>', User::paginate()->all());
    assertType('array<int, App\User>', User::paginate()->items());
    assertType('App\User|null', User::paginate()[0]);

    assertType('Illuminate\Pagination\Paginator<int, App\User>', User::simplePaginate());
    assertType('array<int, App\User>', User::simplePaginate()->all());
    assertType('array<int, App\User>', User::simplePaginate()->items());
    assertType('App\User|null', User::simplePaginate()[0]);

    assertType('Illuminate\Pagination\CursorPaginator<int, App\User>', User::cursorPaginate());
    assertType('array<int, App\User>', User::cursorPaginate()->all());
    assertType('array<int, App\User>', User::cursorPaginate()->items());
    assertType('App\User|null', User::cursorPaginate()[0]);

    assertType('ArrayIterator<int, App\User>', User::query()->paginate()->getIterator());

    // HasMany
    assertType('Illuminate\Pagination\LengthAwarePaginator<int, App\Account>', (new User())->accounts()->paginate());

    // BelongsToMany
    assertType('Illuminate\Pagination\LengthAwarePaginator<int, App\Post>', (new User())->posts()->paginate());
}
