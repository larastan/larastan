<?php

namespace CollectionStubs;

use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;
use function PHPStan\Testing\assertType;

/** @var EloquentCollection<int, User> $collection */
/** @var SupportCollection<string, int> $items */
assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::all()->each(function (User $user, int $key): void {
}));

assertType('Illuminate\Support\Collection<string, int>', $items->each(function (): bool {
    return false;
}));

assertType('Illuminate\Support\Collection<string, string>', $items->map(function (int $item): string {
    return (string) $item;
}));

assertType('Illuminate\Support\Collection<int, mixed>', $collection->pluck('id'));
assertType('Illuminate\Support\Collection<(int|string), App\User>', $collection->keyBy(function (User $user, int $key): string {
    return $user->email;
}));
assertType('Illuminate\Support\Collection<string, Illuminate\Support\Collection<int, int>>', $collection->mapToGroups(function (User $user, int $key): array {
    return ['foo' => $user->id];
}));
assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Database\Eloquent\Collection<int, App\User>>', $collection->groupBy('id'));
assertType('Illuminate\Support\Collection<int, App\User>', User::all()->mapInto(User::class));
assertType('Illuminate\Support\Collection<(int|string), App\User>', $collection->flatMap(function (User $user, int $id): array {
    return [$user];
}));
assertType(
    'Illuminate\Support\Collection<(int|string), App\Account>',
    $collection->flatMap(function (User $user, int $id) {
        return $user->accounts;
    })
);
assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $collection->tap(function ($collection): void {
}));

assertType('Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<int, non-empty-array<string, int|string>>>', collect([
    [
        'id'   => 1,
        'type' => 'A',
    ],
    [
        'id'   => 1,
        'type' => 'B',
    ],
])->groupBy('type'));

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
    ->groupBy('type')
    ->map(function ($grouped, $groupKey): array {
        assertType('(int|string)', $groupKey);
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
assertType('App\User|string', $collection->get(1, 'string'));

assertType('App\User|null', $collection->pull(1));
assertType('App\User|string', $collection->pull(1, 'string'));
assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::all()->filter());
