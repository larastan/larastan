<?php

use App\Transaction;
use App\TransactionCollection;
use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\LazyCollection;

use function PHPStan\Testing\assertType;

/** @var EloquentCollection<int, User> $collection */
/** @var SupportCollection<string, int> $items */
/** @var App\TransactionCollection<int, Transaction> $customEloquentCollection */
/** @var App\UserCollection $secondCustomEloquentCollection */
/** @var LazyCollection<int, User> $lazyCollection */
/** @var User $user */
assertType('Illuminate\Database\Eloquent\Collection<int, int>', EloquentCollection::range(1, 10));
assertType('Illuminate\Support\LazyCollection<int, int>', LazyCollection::range(1, 10));

assertType('Illuminate\Support\Collection<int, mixed>', $collection->collapse());
assertType('Illuminate\Support\Collection<int, mixed>', $items->collapse());

assertType('Illuminate\Database\Eloquent\Collection<int, array<int, App\User|int>>', $collection->crossJoin([1]));

assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $collection->find($items));
assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $collection->find([1]));
assertType('App\User|null', $collection->find($user));
assertType('App\User|null', $collection->find(1));
assertType('App\User|bool', $collection->find(1, false));

assertType('App\TransactionCollection<int, App\Transaction>', $customEloquentCollection->find($items));
assertType('App\TransactionCollection<int, App\Transaction>', $customEloquentCollection->find([1]));
assertType('App\Transaction|null', $customEloquentCollection->find($user));
assertType('App\Transaction|null', $customEloquentCollection->find(1));
assertType('App\Transaction|bool', $customEloquentCollection->find(1, false));

assertType('App\UserCollection', $secondCustomEloquentCollection->find($items));
assertType('App\UserCollection', $secondCustomEloquentCollection->find([1]));
assertType('App\User|null', $secondCustomEloquentCollection->find($user));
assertType('App\User|null', $secondCustomEloquentCollection->find(1));
assertType('App\User|bool', $secondCustomEloquentCollection->find(1, false));

assertType('Illuminate\Support\Collection<int, mixed>', $collection->flatten());
assertType('Illuminate\Support\Collection<int, mixed>', $items->flatten());

assertType('Illuminate\Support\Collection<App\User, int>', $collection->flip());
assertType('Illuminate\Support\Collection<int, string>', $items->flip());

assertType('Illuminate\Database\Eloquent\Collection<(int|string), Illuminate\Database\Eloquent\Collection<(int|string), App\User>>', $collection->groupBy('id'));
assertType('Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), int>>', $items->groupBy('id'));

assertType('Illuminate\Database\Eloquent\Collection<(int|string), App\User>', $collection->keyBy(fn (User $user, int $key): string => $user->email));

assertType('Illuminate\Support\Collection<int, int>', $collection->keys());
assertType('Illuminate\Support\Collection<int, string>', $items->keys());

assertType('Illuminate\Support\Collection<(int|string), mixed>', $collection->pluck(['email']));
assertType('Illuminate\Support\Collection<(int|string), mixed>', $items->pluck('1'));

assertType('Illuminate\Support\Collection<int, int>', $customEloquentCollection->map(fn (Transaction $transaction): int => $transaction->id));
assertType('Illuminate\Support\Collection<int, int>', $secondCustomEloquentCollection->map(fn (User $user): int => $user->id));
assertType('Illuminate\Support\Collection<int, int>', $collection->map(fn (User $user): int => $user->id));
assertType('Illuminate\Support\Collection<string, int>', $items->map(fn (int $value, string $key): int => $value));

assertType('Illuminate\Database\Eloquent\Collection<int, array<int, int>>', $collection->mapToDictionary(fn (User $u) => [$u->id => $u->id]));
assertType('App\TransactionCollection<string, array<int, int>>', $customEloquentCollection->mapToDictionary(fn (Transaction $t) => ['foo'=> $t->id]));
assertType('App\UserCollection', $secondCustomEloquentCollection->mapToDictionary(fn (User $t) => ['foo'=> $t->id]));
assertType('Illuminate\Support\Collection<string, array<int, int>>', $items->mapToDictionary(fn (int $v) => ['foo' => $v]));

assertType('Illuminate\Support\Collection<int, string>', $customEloquentCollection->mapWithKeys(fn (Transaction $transaction): array => [$transaction->id => 'foo']));
assertType('Illuminate\Support\Collection<int, string>', $secondCustomEloquentCollection->mapWithKeys(fn (User $user): array => [$user->id => 'foo']));
assertType('Illuminate\Support\Collection<int, int>', $collection->mapWithKeys(fn (User $user): array => [$user->id => $user->id]));
assertType('Illuminate\Support\Collection<string, int>', $items->mapWithKeys(fn (int $value, string $key): array => ['foo' => $value]));

assertType('Illuminate\Database\Eloquent\Collection<int, App\User|string>', $collection->mergeRecursive([2 => 'foo']));
assertType('App\TransactionCollection<int, App\Transaction>', $customEloquentCollection->mergeRecursive([1 => new Transaction()]));
assertType('App\UserCollection', $secondCustomEloquentCollection->mergeRecursive([1 => new User()]));
assertType('Illuminate\Support\Collection<string, int>', $items->mergeRecursive(['foo' => 2]));

assertType('Illuminate\Database\Eloquent\Collection<(int|string), int>', $collection->combine([1]));
assertType('App\TransactionCollection<(int|string), int>', $customEloquentCollection->combine([1]));
assertType('App\UserCollection', $secondCustomEloquentCollection->combine([1]));
assertType('Illuminate\Support\Collection<(int|string), string>', $items->combine(['foo']));

assertType('App\User|null', $collection->pop(1));
assertType('App\TransactionCollection<int, App\Transaction>', $customEloquentCollection->pop(2));
assertType('App\UserCollection', $secondCustomEloquentCollection->pop(2));
assertType('Illuminate\Support\Collection<int, int>', $items->pop(3));

assertType('App\Role', User::firstOrFail(1)->roles->random());
assertType('App\RoleCollection<int, App\Role>', User::firstOrFail(1)->roles->random(1));
assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $collection->random(1));
assertType('App\TransactionCollection<int, App\Transaction>', $customEloquentCollection->random(2));
assertType('App\UserCollection', $secondCustomEloquentCollection->random(2));
assertType('Illuminate\Support\Collection<int, int>', $items->random(3));

assertType('App\User|null', $collection->shift(1));
assertType('App\TransactionCollection<int, App\Transaction>', $customEloquentCollection->shift(2));
assertType('App\UserCollection', $secondCustomEloquentCollection->shift(2));
assertType('Illuminate\Support\Collection<int, int>', $items->shift(3));

assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Database\Eloquent\Collection<int, App\User>>', $collection->sliding(1));
assertType('App\TransactionCollection<int, App\TransactionCollection<int, App\Transaction>>', $customEloquentCollection->sliding(2));
assertType('App\UserCollection', $secondCustomEloquentCollection->sliding(2));
assertType('Illuminate\Support\Collection<int, Illuminate\Support\Collection<string, int>>', $items->sliding(3));

assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Database\Eloquent\Collection<int, App\User>>', $collection->split(1));
assertType('App\TransactionCollection<int, App\TransactionCollection<int, App\Transaction>>', $customEloquentCollection->split(2));
assertType('App\UserCollection', $secondCustomEloquentCollection->split(2));
assertType('Illuminate\Support\Collection<int, Illuminate\Support\Collection<string, int>>', $items->split(3));

assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Database\Eloquent\Collection<int, App\User>>', $collection->splitIn(1));
assertType('App\TransactionCollection<int, App\TransactionCollection<int, App\Transaction>>', $customEloquentCollection->splitIn(2));
assertType('App\UserCollection', $secondCustomEloquentCollection->splitIn(2));
assertType('Illuminate\Support\Collection<int, Illuminate\Support\Collection<string, int>>', $items->splitIn(3));

assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Database\Eloquent\Collection<int, App\User>>', $collection->chunk(1));
assertType('App\TransactionCollection<int, App\TransactionCollection<int, App\Transaction>>', $customEloquentCollection->chunk(2));
assertType('App\UserCollection', $secondCustomEloquentCollection->chunk(2));
assertType('Illuminate\Support\Collection<int, Illuminate\Support\Collection<string, int>>', $items->chunk(3));

assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Database\Eloquent\Collection<int, App\User>>', $collection->chunkWhile(fn (User $u) => $u->id > 5));
assertType('App\TransactionCollection<int, App\TransactionCollection<int, App\Transaction>>', $customEloquentCollection->chunkWhile(fn (Transaction $t) => $t->id > 5));
assertType('App\UserCollection', $secondCustomEloquentCollection->chunkWhile(fn (User $t) => $t->id > 5));
assertType('Illuminate\Support\Collection<int, Illuminate\Support\Collection<int, int>>', $items->chunkWhile(fn ($v) => $v > 5));

assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $collection->values());
assertType('App\TransactionCollection<int, App\Transaction>', $customEloquentCollection->values());
assertType('App\UserCollection', $secondCustomEloquentCollection->values());
assertType('Illuminate\Support\Collection<int, int>', $items->values());

assertType('Illuminate\Support\Collection<int, Illuminate\Support\Collection<int, App\User|int>>', $collection->zip([1]));
assertType('Illuminate\Support\Collection<int, Illuminate\Support\Collection<int, App\Transaction|string>>', $customEloquentCollection->zip(['foo']));
assertType('Illuminate\Support\Collection<int, Illuminate\Support\Collection<int, App\User|string>>', $secondCustomEloquentCollection->zip(['foo']));
assertType('Illuminate\Support\Collection<int, Illuminate\Support\Collection<int, int|string>>', $items->zip(['foo', 'bar']));

assertType('Illuminate\Database\Eloquent\Collection<int<0, 1>, Illuminate\Database\Eloquent\Collection<int, App\User>>', $collection->partition('foo'));
assertType('App\TransactionCollection<int<0, 1>, App\TransactionCollection<int, App\Transaction>>', $customEloquentCollection->partition('foo'));
assertType('App\UserCollection', $secondCustomEloquentCollection->partition('foo'));
assertType('Illuminate\Support\Collection<int<0, 1>, Illuminate\Support\Collection<string, int>>', $items->partition('foo'));

assertType('Illuminate\Support\Collection<int, App\User|int>', $collection->pad(10, 10));
assertType('Illuminate\Support\Collection<int, App\Transaction|string>', $customEloquentCollection->pad(10, 'foo'));
assertType('Illuminate\Support\Collection<int, App\User|string>', $secondCustomEloquentCollection->pad(10, 'foo'));
assertType('Illuminate\Support\Collection<int, int|string>', $items->pad(1, 'bar'));

assertType('Illuminate\Support\Collection<(int|string), int>', $collection->countBy('email'));
assertType('Illuminate\Support\Collection<(int|string), int>', $customEloquentCollection->countBy('foo'));
assertType('Illuminate\Support\Collection<(int|string), int>', $secondCustomEloquentCollection->countBy('foo'));
assertType('Illuminate\Support\Collection<(int|string), int>', $items->countBy('bar'));

////////////////////////////
// EnumeratesValues Trait //
////////////////////////////

assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', EloquentCollection::make([new User()]));
assertType('App\TransactionCollection<int, App\Transaction>', TransactionCollection::make([new Transaction()]));
assertType('Illuminate\Support\Collection<int, int>', SupportCollection::make([1, 2, 3]));

assertType('Illuminate\Database\Eloquent\Collection<(int|string), App\User>', EloquentCollection::wrap([new User()]));
assertType('App\TransactionCollection<(int|string), App\Transaction>', TransactionCollection::wrap([new Transaction()]));
assertType('Illuminate\Support\Collection<(int|string), int>', SupportCollection::wrap([1, 2, 3]));

assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', EloquentCollection::times(10, fn ($int) => new User));
assertType('App\TransactionCollection<int, App\Transaction>', TransactionCollection::times(10, fn ($int) => new Transaction));
assertType('Illuminate\Support\Collection<int, int>', SupportCollection::times(10, fn ($int) => 5));
assertType('Illuminate\Support\LazyCollection<int, int>', LazyCollection::times(10, fn ($int) => 5));

// In runtime it returns `Illuminate\Support\Collection<string, Illuminate\Database\Eloquent\Collection<int, int>>`
// Might be fixed in Laravel or needs a separate extension
assertType(
    'Illuminate\Database\Eloquent\Collection<string, Illuminate\Database\Eloquent\Collection<int, int>>',
    $collection->mapToGroups(fn (User $user, int $key): array => ['foo' => $user->id])
);

assertType(
    'Illuminate\Database\Eloquent\Collection<int, int>',
    $collection->flatMap(function (User $user, int $id) {
        return [$user->id];
    })
);

assertType(
    'Illuminate\Support\Collection<int, int>',
    $items->flatMap(function (int $int) {
        return [$int * 2];
    })
);

assertType(
    'Illuminate\Support\LazyCollection<int, int>',
    $lazyCollection->flatMap(function (User $user, int $id) {
        return [$user->id];
    })
);

assertType(
    'Illuminate\Support\LazyCollection<int, int>',
    LazyCollection::times(10, fn ($int) => 5)->flatMap(fn (int $i) => [$i * 2]),
);

assertType('Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), array{id: int, type: string}>>', collect([
    [
        'id'   => 1,
        'type' => 'A',
    ],
    [
        'id'   => 1,
        'type' => 'B',
    ],
])->groupBy('type'));
