<?php

declare(strict_types=1);

namespace PaginatorExtension;

use App\User;
use function PHPStan\Testing\assertType;

assertType('Illuminate\Pagination\LengthAwarePaginator<App\User>', User::paginate());
assertType('array<App\User>', User::paginate()->items());

assertType('Illuminate\Pagination\Paginator<App\User>', User::simplePaginate());
assertType('array<App\User>', User::simplePaginate()->items());

assertType('Illuminate\Pagination\CursorPaginator<App\User>', User::cursorPaginate());
assertType('array<App\User>', User::cursorPaginate()->items());

assertType('ArrayIterator<mixed, App\User>', User::query()->paginate()->getIterator());
