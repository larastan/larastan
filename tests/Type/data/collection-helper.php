<?php

namespace CollectionHelper;

use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use function PHPStan\Testing\assertType;

assertType('Illuminate\Support\Collection', collect());
assertType('Illuminate\Support\Collection<0, 1>', collect(1));
assertType('Illuminate\Support\Collection<0, \'foo\'>', collect('foo'));
assertType('Illuminate\Support\Collection<0, 3.14>', collect(3.14));
assertType('Illuminate\Support\Collection<0, true>', collect(true));
assertType('Illuminate\Support\Collection<int|string, mixed>', collect([]));
assertType('Illuminate\Support\Collection<int, int>', collect([1, 2, 3]));
assertType('Illuminate\Support\Collection<int, string>', collect(['foo', 'bar', 'baz']));
assertType('Illuminate\Support\Collection<int, float>', collect([1.0, 2.0, 3.0]));
assertType('Illuminate\Support\Collection<int, float|int|string>', collect([1, 'foo', 1.0]));
assertType("Illuminate\Support\Collection<int, non-empty-array<int, string>>", collect([['a', 'b', 'c']]));
assertType("Illuminate\Support\Collection<int, non-empty-array<int, string>>", collect([['a', 'b', 'c']])->push(array_fill(0, 3, 'x')));

/**  @phpstan-param EloquentCollection<int> $eloquentCollection */
function eloquentCollectionInteger(EloquentCollection $eloquentCollection): void
{
    assertType('Illuminate\Support\Collection<int, int>', collect($eloquentCollection));
}

/**  @phpstan-param EloquentCollection<User> $eloquentCollection */
function eloquentCollectionUser(EloquentCollection $eloquentCollection): void
{
    assertType('Illuminate\Support\Collection<int, App\User>', collect($eloquentCollection));
}

/**
 * @phpstan-param \Traversable<int, int> $foo
 * @phpstan-return Collection<int, int>
 */
function testIterable(\Traversable $foo): void
{
    assertType('Illuminate\Support\Collection<int, int>', collect($foo));
}
