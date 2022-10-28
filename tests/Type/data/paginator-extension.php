<?php

declare(strict_types=1);

namespace PaginatorExtension;

use App\User;
use function PHPStan\Testing\assertType;

assertType('Illuminate\Pagination\LengthAwarePaginator<App\User>', User::paginate());
assertType('array<App\User>', User::paginate()->all());
assertType('array<App\User>', User::paginate()->items());
assertType('App\User|null', User::paginate()[0]);

assertType('Illuminate\Pagination\Paginator<App\User>', User::simplePaginate());
assertType('array<App\User>', User::simplePaginate()->all());
assertType('array<App\User>', User::simplePaginate()->items());
assertType('App\User|null', User::simplePaginate()[0]);

assertType('Illuminate\Pagination\CursorPaginator<App\User>', User::cursorPaginate());
assertType('array<App\User>', User::cursorPaginate()->all());
assertType('array<App\User>', User::cursorPaginate()->items());
assertType('App\User|null', User::cursorPaginate()[0]);

assertType('ArrayIterator<(int|string), App\User>', User::query()->paginate()->getIterator());

// HasMany
assertType('Illuminate\Pagination\LengthAwarePaginator<App\Account>', (new User())->accounts()->paginate());

// BelongsToMany
assertType('Illuminate\Pagination\LengthAwarePaginator<App\Post>', (new User())->posts()->paginate());
