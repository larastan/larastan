<?php

namespace CollectionHelper;

use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

use function PHPStan\Testing\assertType;

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

/**  @phpstan-param EloquentCollection<int, int> $eloquentCollection */
function eloquentCollectionInteger(EloquentCollection $eloquentCollection): void
{
    assertType('Illuminate\Support\Collection<int, int>', collect($eloquentCollection));
}

/**  @phpstan-param EloquentCollection<int, User> $eloquentCollection */
function eloquentCollectionUser(EloquentCollection $eloquentCollection): void
{
    assertType('Illuminate\Support\Collection<int, App\User>', collect($eloquentCollection));
}

/**
 * @phpstan-param \Traversable<int, int> $foo
 *
 * @phpstan-return Collection<int, int>
 */
function testIterable(\Traversable $foo): void
{
    assertType('Illuminate\Support\Collection<int, int>', collect($foo));
}

// Something with generics
/**
 * @template T
 */
class Ok {
    /**
     * @param T $value
     */
    public function __construct(public mixed $value) {
    }
}

/**
 * @template T
 */
class Some
{
    /**
     * @param T $value
     */
    public function __construct(public mixed $value)
    {
    }
}

assertType('Illuminate\Support\Collection<int, CollectionHelper\Ok<CollectionHelper\Some<int>>>', collect([new Ok(new Some(42))]));
assertType('Illuminate\Support\Collection<int, CollectionHelper\Ok<CollectionHelper\Some<int>>>', collect([42])->map(fn (int $i) => new Ok(new Some($i))));
