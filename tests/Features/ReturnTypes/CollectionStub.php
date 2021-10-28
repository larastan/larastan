<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;
use function PHPStan\Testing\assertType;
use Traversable;

class CollectionStub
{
    /**
     * @return EloquentCollection<User>
     */
    public function testEachUserParam(): EloquentCollection
    {
        return User::all()->each(function (User $user, int $key): void {
            echo $user->id.$key;
        });
    }

    /**
     * @param  SupportCollection<string, int>  $items
     * @return SupportCollection<string, int>
     */
    public function testEachWithoutParams(SupportCollection $items): SupportCollection
    {
        return $items->each(function (): bool {
            return false;
        });
    }

    /**
     * @param  SupportCollection<int>  $items
     * @return SupportCollection<string>
     */
    public function testMap(SupportCollection $items): SupportCollection
    {
        return $items->map(function (int $item): string {
            return (string) $item;
        });
    }

    /**
     * @param  EloquentCollection<User>  $collection
     */
    public function testMapPartition(EloquentCollection $collection): SupportCollection
    {
        return $collection->partition(function (User $user) {
            return $user->accounts()->count() > 1;
        })->map(function (EloquentCollection $users) {
            return $users->count();
        });
    }

    /**
     * @param  EloquentCollection<User>  $collection
     * @return mixed
     */
    public function testPluck(EloquentCollection $collection)
    {
        return $collection->pluck('id')->first();
    }

    /**
     * @param  EloquentCollection<User>  $collection
     * @return SupportCollection<int>
     */
    public function testMapToGroups(EloquentCollection $collection)
    {
        return $collection->mapToGroups(function (User $user, int $key): array {
            return [$user->name => $user->id];
        })->map(function (EloquentCollection $items, $name) {
            return $items->first();
        })->take(3);
    }

    /**
     * @param  EloquentCollection<User>  $collection
     * @return EloquentCollection<User>
     */
    public function testKeyBy(EloquentCollection $collection)
    {
        return $collection->keyBy(function (User $user, int $key): string {
            return $user->email;
        });
    }

    /**
     * @return EloquentCollection<EloquentCollection<User>>
     */
    public function testGroupBy()
    {
        return User::all()->groupBy('id');
    }

    /**
     * @return SupportCollection<User>
     */
    public function testMapInto()
    {
        return User::all()->map(function (User $user): array {
            return $user->toArray();
        })->mapInto(User::class);
    }

    /**
     * @return EloquentCollection<int>
     */
    public function testTimes(): EloquentCollection
    {
        return EloquentCollection::times(4);
    }

    /**
     * @return SupportCollection<int>
     */
    public function testTimesSupport(): SupportCollection
    {
        return SupportCollection::times(4);
    }

    /**
     * @return SupportCollection<string>
     */
    public function testTimesCallback(): SupportCollection
    {
        return EloquentCollection::times(4, function (): string {
            return 'a string';
        });
    }

    /**
     * @param  SupportCollection<array<string>>  $collection
     * @return SupportCollection<string>
     */
    public function testMapSpread(SupportCollection $collection)
    {
        return $collection->mapSpread(function (...$values): string {
            return $values[0];
        });
    }

    /**
     * @param  EloquentCollection<User>  $collection
     * @return SupportCollection<User>
     */
    public function testFlatMap(EloquentCollection $collection)
    {
        return $collection->flatMap(function (User $user, int $id): array {
            return [$user];
        })->map(function (User $user, int $id): User {
            return $user;
        });
    }

    /**
     * @param  EloquentCollection<User>  $collection
     */
    public function testFlatMapWithCollection(EloquentCollection $collection): void
    {
        assertType(
            'Illuminate\Support\Collection<int, App\Account>',
            $collection->flatMap(function (User $user, int $id) {
                return $user->accounts;
            })
        );
    }

    /**
     * @param  EloquentCollection<User>  $items
     * @return EloquentCollection<User>
     */
    public function testTap(EloquentCollection $items): EloquentCollection
    {
        return $items->tap(function ($collection): void {
            $first = $collection->first();
            if (is_null($first)) {
                echo 'Null';
            } else {
                echo $first->id;
            }
        });
    }
}
