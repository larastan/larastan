<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Traversable;

class CollectExtension
{
    /** @phpstan-return Collection<int, mixed> */
    public function testNoArguments(): Collection
    {
        return collect();
    }

    /** @phpstan-return Collection<0, 1> */
    public function testInteger(): Collection
    {
        return collect(1);
    }

    /** @phpstan-return Collection<0, 'foo'> */
    public function testString(): Collection
    {
        return collect('foo');
    }

    /** @phpstan-return Collection<0, 3.14> */
    public function testFloat(): Collection
    {
        return collect(3.14);
    }

    /** @phpstan-return Collection<0, true> */
    public function testBool(): Collection
    {
        return collect(true);
    }

    /** @phpstan-return Collection<array-key, mixed> */
    public function testEmptyArray(): Collection
    {
        return collect([]);
    }

    /** @phpstan-return Collection<int, int> */
    public function testIntegerArray(): Collection
    {
        return collect([1, 2, 3]);
    }

    /** @phpstan-return Collection<int, string> */
    public function testStringArray(): Collection
    {
        return collect(['foo', 'bar', 'baz']);
    }

    /** @phpstan-return Collection<int, float> */
    public function testFloatArray(): Collection
    {
        return collect([1.0, 2.0, 3.0]);
    }

    /** @phpstan-return Collection<int, int|string|float> */
    public function testMixedTypeArray(): Collection
    {
        return collect([1, 'foo', 1.0]);
    }

    /**
     * @phpstan-param EloquentCollection<int> $eloquentCollection
     * @phpstan-return Collection<int, int>
     */
    public function testEloquentCollectionInteger(EloquentCollection $eloquentCollection): Collection
    {
        return collect($eloquentCollection);
    }

    /**
     * @phpstan-param EloquentCollection<User> $eloquentCollection
     * @phpstan-return Collection<int, User>
     */
    public function testEloquentCollectionModel(EloquentCollection $eloquentCollection): Collection
    {
        return collect($eloquentCollection);
    }

    /**
     * @phpstan-param Traversable<int, int> $foo
     * @phpstan-return Collection<int, int>
     */
    public function testIterable(Traversable $foo): Collection
    {
        return collect($foo);
    }
}
