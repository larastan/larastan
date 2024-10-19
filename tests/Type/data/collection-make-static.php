<?php

namespace CollectionMake;

use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;
use Traversable;

use function PHPStan\Testing\assertType;

/**
 * @param EloquentCollection<int, \App\User> $users
 * @param Traversable<int, int> $traversable
 */
function test(EloquentCollection $users, Traversable $traversable): void
{
    // assertType('Illuminate\Support\Collection<int|string, mixed>', SupportCollection::make([]));
    assertType('Illuminate\Support\Collection<int, int>', SupportCollection::make([1, 2, 3]));
    assertType('Illuminate\Support\Collection<int, string>', SupportCollection::make(['foo', 'bar', 'baz']));
    assertType('Illuminate\Support\Collection<int, float>', SupportCollection::make([1.0, 2.0, 3.0]));
    assertType('Illuminate\Support\Collection<int, float|int|string>', SupportCollection::make([1, 'foo', 1.0]));

    assertType('Illuminate\Support\Collection<int, App\User>', SupportCollection::make($users));

    /** @var EloquentCollection<int, User> $users */
    assertType('Illuminate\Support\Collection<int, App\User>', SupportCollection::make($users));

    assertType('Illuminate\Support\Collection<int, int>', SupportCollection::make($traversable));
}
