<?php

namespace CollectionStubs;

use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;
use function PHPStan\Testing\assertType;

/** @var EloquentCollection<User> $collection */
/** @var SupportCollection<string, int> $items */

assertType('Illuminate\Database\Eloquent\Collection<App\User>', User::all()->each(function (User $user, int $key): void {}));

assertType('Illuminate\Support\Collection<string, int>', $items->each(function (): bool {
    return false;
}));


assertType('Illuminate\Support\Collection<string, string>', $items->map(function (int $item): string {
    return (string) $item;
}));


assertType('Illuminate\Support\Collection<int, mixed>', $collection->pluck('id'));
assertType('Illuminate\Database\Eloquent\Collection<App\User>', $collection->keyBy(function (User $user, int $key): string {
    return $user->email;
}));
assertType('Illuminate\Support\Collection<string, Illuminate\Support\Collection<int, int>>', $collection->mapToGroups(function (User $user, int $key): array {
    return [$user->name => $user->id];
}));
assertType('Illuminate\Support\Collection<int, Illuminate\Database\Eloquent\Collection<App\User>>', $collection->groupBy('id'));
assertType('Illuminate\Support\Collection<int, App\User>', User::all()->mapInto(User::class));
assertType('Illuminate\Support\Collection<int, App\User>', $collection->flatMap(function (User $user, int $id): array {
    return [$user];
}));
assertType(
    'Illuminate\Support\Collection<int, App\Account>',
    $collection->flatMap(function (User $user, int $id) {
        return $user->accounts;
    })
);
assertType('Illuminate\Database\Eloquent\Collection<App\User>', $collection->tap(function ($collection): void {
    $first = $collection->first();
    if (is_null($first)) {
        echo 'Null';
    } else {
        echo $first->id;
    }
}));
