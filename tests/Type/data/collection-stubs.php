<?php

namespace CollectionStubs;

use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;

use function PHPStan\Testing\assertType;

/** @var EloquentCollection<int, User> $collection */
/** @var SupportCollection<string, int> $items */
/** @var SupportCollection<int, User> $collectionOfUsers */
/** @var User $user */
/** @var bool $preserve */
assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::all()->each(function (User $user, int $key): void {
}));

assertType('Illuminate\Support\Collection<string, int>', $items->each(function (): bool {
    return false;
}));

assertType('Illuminate\Support\Collection<string, string>', $items->map(function (int $item): string {
    return (string) $item;
}));

assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $collection->find($items));
assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $collection->find([1]));
assertType('App\User|null', $collection->find($user));
assertType('App\User|null', $collection->find(1));
assertType('App\User|bool', $collection->find(1, false));

assertType('Illuminate\Support\Collection<(int|string), mixed>', $collection->pluck('id'));

assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::all()->mapInto(User::class));
assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $collection->flatMap(function (User $user, int $id): array {
    return [$user];
}));

assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $collection->tap(function ($collection): void {
}));

$foo = collect([
    [
        'id'   => 1,
        'type' => 'A',
    ],
    [
        'id'   => 1,
        'type' => 'B',
    ],
]);

$foo
    ->groupBy('type', $preserve)
    ->map(function ($grouped, $groupKey): array {
        assertType('string', $groupKey);
    });

$foo
    ->groupBy('type', true)
    ->map(function ($grouped, $groupKey): array {
        assertType('string', $groupKey);
    });

$foo
    ->groupBy('type', false)
    ->map(function ($grouped, $groupKey): array {
        assertType('string', $groupKey);
    });

assertType('App\User|null', $collection->first());
assertType('App\User|bool', $collection->first(null, false));
assertType('App\User|null', $collection->first(function ($user) {
    assertType('App\User', $user);

    return $user->id > 1;
}));
assertType('App\User|bool', $collection->first(function (User $user) {
    assertType('App\User', $user);

    return $user->id > 1;
}, function () {
    return false;
}));

assertType('App\User|null', $collection->firstWhere('blocked'));
assertType('App\User|null', $collection->firstWhere('blocked', true));
assertType('App\User|null', $collection->firstWhere('blocked', '=', true));

assertType('App\User|null', $collection->last());
assertType('App\User|bool', $collection->last(null, false));
assertType('App\User|null', $collection->last(function (User $user) {
    return $user->id > 1;
}));
assertType('App\User|bool', $collection->last(function (User $user) {
    return $user->id > 1;
}, function () {
    return false;
}));

assertType('App\User|null', $collection->get(1));
assertType('App\User', $collection->get(1, new User()));

assertType('App\User|null', $collection->pull(1));
assertType('App\User', $collection->pull(1, new User()));
assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::all()->filter());

assertType('App\User', $collection->random());
assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $collection->random(5));

assertType('App\User', $collectionOfUsers->random());
assertType('Illuminate\Support\Collection<int, App\User>', $collectionOfUsers->random(5));

assertType('App\User|null', $collection->pop());
assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $collection->pop(5));

assertType('App\User|null', $collectionOfUsers->pop());
assertType('Illuminate\Support\Collection<int, App\User>', $collectionOfUsers->pop(5));

assertType('App\User|null', $collection->shift());
assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $collection->shift(5));

assertType('App\User|null', $collectionOfUsers->shift());
assertType('Illuminate\Support\Collection<int, App\User>', $collectionOfUsers->shift(5));
