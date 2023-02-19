<?php

namespace CollectionWhereNotNull;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use function PHPStan\Testing\assertType;

assertType('Illuminate\Support\Collection<int, int|string>', collect([1, 2, null, '', 'hello'])->whereNotNull());
assertType('Illuminate\Support\Collection<int, int|string>', collect([1, 2, null, '', 'hello'])->whereNotNull());

/** @param \Illuminate\Database\Eloquent\Collection<int, \App\User> $foo */
function objectAndParam(EloquentCollection $foo): void
{
    assertType("Illuminate\Database\Eloquent\Collection<int, App\User>", $foo->whereNotNull('blocked'));
}

/** @param \Illuminate\Database\Eloquent\Collection<int, ?\App\User> $foo */
function objectOrNullAndParam(EloquentCollection $foo): void
{
    assertType("Illuminate\Database\Eloquent\Collection<int, App\User>", $foo->whereNotNull('blocked'));
}

/** @param \Illuminate\Database\Eloquent\Collection<int, ?\App\User> $foo */
function objectOrNull(EloquentCollection $foo): void
{
    assertType("Illuminate\Database\Eloquent\Collection<int, App\User>", $foo->whereNotNull());
}
