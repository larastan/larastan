<?php

namespace CollectionValuesList;

use App\User;
use ArrayObject;
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
    public function test(): void
    {
        assertType('list<TValue (class CollectionValuesList\InheritingCollection, argument)>', $this->values()->all());
        assertType('array<TKey of (int|string) (class CollectionValuesList\InheritingCollection, argument), TValue (class CollectionValuesList\InheritingCollection, argument)>', $this->all());
    }
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
class ArrayObjectCollection extends Collection
{
    /** @return ArrayObject<TKey, TValue> */
    public function all()
    {
        return new ArrayObject($this->items);
    }
}

/** @extends Collection<string, User> */
class StringKeyedCollection extends Collection
{
}

interface Marker
{
}

class Team
{
    /** @return Collection<string, User> */
    public function values(): Collection
    {
        return new Collection();
    }
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
 * @param ValuesOverridingCollection<string, User>            $valuesOverriding
 * @param Collection<string, User>&Marker                     $intersection
 * @param TCollection                                         $template
 *
 * @template TCollection of Collection<string, User>
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
    ValuesOverridingCollection $valuesOverriding,
    Collection $intersection,
    Collection $template,
    Holder|null $holder,
): void {
    assertType('list<App\User>', $collection->values()->all());
    assertType('list<App\User>', $eloquentCollection->values()->all());
    assertType('list<App\User>', $lazyCollection->values()->all());
    assertType('list<App\User>', $enumerable->values()->all());
    assertType('list<App\User>', $inheriting->values()->all());
    assertType('list<App\User>', $stringKeyed->values()->all());
    assertType('list<App\User>', $valuesOverriding->values()->all());
    assertType('list<App\User>', $intersection->values()->all());
    assertType('list<App\User>', $template->values()->all());
    assertType('list<App\User>', $collection->filter()->values()->all());
    assertType('list<App\User>', $collection->VALUES()->ALL());
    assertType('list<int|string>', $union->values()->all());
    assertType('list<int|string>', collect([1, 2, 'three'])->values()->all());
    assertType('list', collect([])->values()->all());

    assertType('list<App\User>|null', $nullable?->values()->all());
    assertType('list<App\User>|null', $nullable?->values()?->all());
    assertType('list<App\User>|null', $holder?->users->values()->all());
}

/**
 * @param Collection<string, User>            $collection
 * @param Collection<int, User>               $intKeyed
 * @param ArrayObjectCollection<string, User> $arrayObject
 */
function testKeysAreKept(
    Collection $collection,
    Collection $intKeyed,
    ArrayObjectCollection $arrayObject,
    Team $team,
    mixed $mixed,
): void {
    assertType('array<string, App\User>', $collection->all());
    assertType('array<int, App\User>', $intKeyed->all());
    assertType('array<string, mixed>', $collection->toArray());
    assertType('array<int, App\User>', $collection->values()->filter()->all());
    assertType('array<int, mixed>', $collection->values()->toArray());

    $values = $collection->values();
    assertType('array<int, App\User>', $values->all());

    assertType('ArrayObject<int, App\User>', $arrayObject->values()->all());
    assertType('array<string, App\User>', $team->values()->all());
    assertType('mixed', $mixed->values()->all());
    assertType('Closure(): array<int, App\User>', $collection->values()->all(...));
}
