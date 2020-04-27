<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use App\Role;
use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;

class CollectionStub
{
    /**
     * @return EloquentCollection<User>
     */
    public function testEachUserParam(): EloquentCollection
    {
        return User::all()->each(function (User $user, int $key): void {
            echo $user->id . $key;
        });
    }

    /**
     * @param SupportCollection<class-string, int> $items
     * @return SupportCollection<class-string, int>
     */
    public function testEachWithoutParams(SupportCollection $items): SupportCollection
    {
        return $items->each(function (): bool {
            return false;
        });
    }

    /**
     * @param SupportCollection<int> $items
     * @return SupportCollection<string>
     */
    public function testMap(SupportCollection $items): SupportCollection
    {
        return $items->map(function (int $item): string {
            return (string) $item;
        });
    }

    /**
     * @param EloquentCollection<User> $items
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
