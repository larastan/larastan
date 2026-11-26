<?php

namespace CollectionValuesList;

use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\LazyCollection;

use function PHPStan\Testing\assertType;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @extends Collection<TKey, TValue>
 */
class InheritingCollection extends Collection
{
}

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @extends Collection<TKey, TValue>
 */
class ValuesOverridingCollection extends Collection
{
    /** @return static<int, TValue> */
    public function values()
    {
        return $this;
    }
}

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @extends Collection<TKey, TValue>
 */
class AllOverridingCollection extends Collection
{
    /** @return array<TKey, TValue> */
    public function all()
    {
        return ['first' => $this->first()];
    }
}

/** @extends Collection<string, User> */
class StringKeyedCollection extends Collection
{
}

class Holder
{
    /** @param Collection<string, User> $users */
    public function __construct(public Collection $users)
    {
    }
}

/**
 * @param Collection<string, User>                            $collection
 * @param EloquentCollection<int, User>                       $eloquentCollection
 * @param LazyCollection<string, User>                        $lazyCollection
 * @param Enumerable<string, User>                            $enumerable
 * @param Collection<string, int>|LazyCollection<int, string> $union
 * @param Collection<string, User>|null                       $nullable
 * @param InheritingCollection<string, User>                  $inheriting
 */
function test(
    Collection $collection,
    EloquentCollection $eloquentCollection,
    LazyCollection $lazyCollection,
    Enumerable $enumerable,
    Collection|LazyCollection $union,
    Collection|null $nullable,
    InheritingCollection $inheriting,
    StringKeyedCollection $stringKeyed,
    Holder|null $holder,
): void {
    assertType('list<App\User>', $collection->values()->all());
    assertType('list<App\User>', $eloquentCollection->values()->all());
    assertType('list<App\User>', $lazyCollection->values()->all());
    assertType('list<App\User>', $enumerable->values()->all());
    assertType('list<App\User>', $inheriting->values()->all());
    assertType('list<App\User>', $stringKeyed->values()->all());
    assertType('list<App\User>', $collection->filter()->values()->all());
    assertType('list<App\User>', $collection->VALUES()->ALL());
    assertType('list<int|string>', $union->values()->all());
    assertType('list<int|string>', collect([1, 2, 'three'])->values()->all());

    assertType('list<App\User>|null', $nullable?->values()->all());
    assertType('list<App\User>|null', $holder?->users->values()->all());
}

/**
 * @param Collection<string, User>                                              $collection
 * @param Collection<int, User>                                                 $intKeyed
 * @param ValuesOverridingCollection<string, User>                              $valuesOverriding
 * @param AllOverridingCollection<string, User>                                 $allOverriding
 * @param LazyCollection<string, User>|ValuesOverridingCollection<string, User> $union
 */
function testKeysAreKept(
    Collection $collection,
    Collection $intKeyed,
    ValuesOverridingCollection $valuesOverriding,
    AllOverridingCollection $allOverriding,
    LazyCollection|ValuesOverridingCollection $union,
): void {
    assertType('array<string, App\User>', $collection->all());
    assertType('array<int, App\User>', $intKeyed->all());
    assertType('array<string, mixed>', $collection->toArray());
    assertType('array<int, App\User>', $collection->values()->filter()->all());
    assertType('array<int, mixed>', $collection->values()->toArray());

    $values = $collection->values();
    assertType('array<int, App\User>', $values->all());

    assertType('array<int, App\User>', $valuesOverriding->values()->all());
    assertType('array<int, App\User>', $allOverriding->values()->all());
    assertType('array<int, App\User>', $union->values()->all());
}
