<?php

namespace CollectionMake;

use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;
use function PHPStan\Testing\assertType;

//assertType('Illuminate\Support\Collection<int|string, mixed>', SupportCollection::make([]));
assertType('Illuminate\Support\Collection<int, int>', SupportCollection::make([1, 2, 3]));
assertType('Illuminate\Support\Collection<int, string>', SupportCollection::make(['foo', 'bar', 'baz']));
assertType('Illuminate\Support\Collection<int, float>', SupportCollection::make([1.0, 2.0, 3.0]));
assertType('Illuminate\Support\Collection<int, float|int|string>', SupportCollection::make([1, 'foo', 1.0]));

/**  @phpstan-param EloquentCollection<int, \App\User> $eloquentCollection */
function eloquentCollectionInteger(EloquentCollection $eloquentCollection): void
{
    assertType('Illuminate\Support\Collection<(int|string), mixed>', SupportCollection::make($eloquentCollection));
}

/**  @phpstan-param EloquentCollection<int, User> $eloquentCollection */
function eloquentCollectionUser(EloquentCollection $eloquentCollection): void
{
    assertType('Illuminate\Support\Collection<(int|string), mixed>', SupportCollection::make($eloquentCollection));
}

/**
 * @phpstan-param \Traversable<int, int> $foo
 */
function testIterable(\Traversable $foo): void
{
    assertType('Illuminate\Support\Collection<int, int>', SupportCollection::make($foo));
}
