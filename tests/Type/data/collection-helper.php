<?php

namespace CollectionHelper;

use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Traversable;

use function PHPStan\Testing\assertType;

/**
 * @param EloquentCollection<int, int> $ints
 * @param EloquentCollection<int, User> $users
 * @param Traversable<int, int> $traversable
 */
function test(
    EloquentCollection $ints,
    EloquentCollection $users,
    Traversable $traversable
): void {
    assertType('Illuminate\Support\Collection<(int|string), mixed>', collect());
    assertType('Illuminate\Support\Collection<0, 1>', collect(1));
    assertType('Illuminate\Support\Collection<0, \'foo\'>', collect('foo'));
    assertType('Illuminate\Support\Collection<0, 3.14>', collect(3.14));
    assertType('Illuminate\Support\Collection<0, true>', collect(true));
    assertType('Illuminate\Support\Collection<(int|string), mixed>', collect([]));
    assertType('Illuminate\Support\Collection<int, int>', collect([1, 2, 3]));
    assertType('Illuminate\Support\Collection<int, string>', collect(['foo', 'bar', 'baz']));
    assertType('Illuminate\Support\Collection<int, float>', collect([1.0, 2.0, 3.0]));
    assertType('Illuminate\Support\Collection<int, float|int|string>', collect([1, 'foo', 1.0]));
    assertType("Illuminate\Support\Collection<int, array{string, string, string}>", collect([['a', 'b', 'c']]));
    assertType("Illuminate\Support\Collection<int, array{string, string, string}>", collect([['a', 'b', 'c']])->push(array_fill(0, 3, 'x')));
    assertType("Illuminate\Support\Collection<int, App\User>", collect([new User, new User]));
    assertType("Illuminate\Support\Collection<int, array{App\User, App\User, App\User}>", collect([[new User, new User, new User]]));
    assertType('Illuminate\Support\Collection<int, int>', collect($ints));
    assertType('Illuminate\Support\Collection<int, App\User>', collect($users));
    assertType('Illuminate\Support\Collection<int, int>', collect($traversable));
}
