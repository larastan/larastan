<?php

namespace EloquentCollectionMap;

use App\Account;
use App\ModelWithOnlyValueGenericCollection;
use App\OnlyValueGenericCollection;
use App\Transaction;
use App\TransactionCollection;
use App\User;
use App\UserCollection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\LazyCollection;

use function PHPStan\Testing\assertType;

/**
 * A callback that never returns a model always produces a base collection.
 *
 * @param EloquentCollection<int, User>                                   $users
 * @param EloquentCollection<string, User>                                $keyedUsers
 * @param TransactionCollection<int, Transaction>                         $transactions
 * @param OnlyValueGenericCollection<ModelWithOnlyValueGenericCollection> $onlyValue
 */
function neverReturnsModel(
    EloquentCollection $users,
    EloquentCollection $keyedUsers,
    TransactionCollection $transactions,
    UserCollection $userCollection,
    OnlyValueGenericCollection $onlyValue,
): void {
    assertType('Illuminate\Support\Collection<int, int<0, max>>', $users->map(fn ($user) => $user->id));
    assertType('Illuminate\Support\Collection<int, string>', $users->map(function ($user) {
        return $user->name;
    }));
    assertType('Illuminate\Support\Collection<string, string>', $keyedUsers->map(fn ($user, $key) => $key));
    assertType('Illuminate\Support\Collection<int, int>', $transactions->map(fn ($transaction) => $transaction->id));
    assertType('Illuminate\Support\Collection<int, string>', $userCollection->map(fn ($user) => $user->name));
    assertType('Illuminate\Support\Collection<int, int>', $onlyValue->map(fn ($model) => 1));
    assertType('Illuminate\Support\Collection<int, App\AccountCollection<int, App\Account>>', $users->map(fn ($user) => $user->accounts));

    // Constant values are generalized, so the resulting collection can be written to and returned.
    assertType('Illuminate\Support\Collection<int, string>', $users->map(fn ($user) => 'foo'));
    assertType('Illuminate\Support\Collection<int, bool>', $users->map(fn ($user) => true));
    assertType('Illuminate\Support\Collection<int, array{id: int, name: string}>', $users->map(fn ($user) => ['id' => $user->id, 'name' => $user->name]));
    assertType('Illuminate\Support\Collection<int, array{id: int, name: string}>', User::query()->get()->map(fn ($user) => ['id' => $user->id, 'name' => $user->name]));

    assertType('Illuminate\Support\Collection<int, float>', $users->mapWithKeys(fn ($user) => [$user->id => 0.0]));
    assertType('Illuminate\Support\Collection<string, int<0, max>>', $users->mapWithKeys(fn ($user) => ['foo' => $user->id]));
    assertType('Illuminate\Support\Collection<string, array{a: int}>', $users->mapWithKeys(fn ($user) => [$user->name => ['a' => 1]]));
    assertType('Illuminate\Support\Collection<string, int|string>', $users->mapWithKeys(fn ($user) => ['a' => 1, 'b' => 'x']));
    assertType('Illuminate\Support\Collection<int, string>', $transactions->mapWithKeys(fn ($transaction) => [$transaction->id => 'foo']));
    assertType('Illuminate\Support\Collection<string, int>', $onlyValue->mapWithKeys(fn ($model) => ['a' => 1]));
}

/**
 * A callback that always returns a model keeps the collection class it was called on.
 *
 * @param EloquentCollection<int, User>                                   $users
 * @param EloquentCollection<string, User>                                $keyedUsers
 * @param TransactionCollection<int, Transaction>                         $transactions
 * @param OnlyValueGenericCollection<ModelWithOnlyValueGenericCollection> $onlyValue
 */
function alwaysReturnsModel(
    EloquentCollection $users,
    EloquentCollection $keyedUsers,
    TransactionCollection $transactions,
    UserCollection $userCollection,
    OnlyValueGenericCollection $onlyValue,
): void {
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $users->map(fn ($user) => $user));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\Account>', $users->map(fn ($user) => new Account()));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\Account|App\User>', $users->map(fn ($user) => $user->id > 5 ? $user : new Account()));
    assertType('Illuminate\Database\Eloquent\Collection<string, App\User>', $keyedUsers->map(fn ($user) => $user));
    assertType('App\TransactionCollection<int, App\Transaction>', $transactions->map(fn ($transaction) => $transaction));
    assertType('App\TransactionCollection<int, App\User>', $transactions->map(fn ($transaction) => new User()));
    assertType('App\UserCollection', $userCollection->map(fn ($user) => $user));
    assertType('App\OnlyValueGenericCollection<App\ModelWithOnlyValueGenericCollection>', $onlyValue->map(fn ($model) => $model));
    assertType('App\OnlyValueGenericCollection<App\User>', $onlyValue->map(fn ($model) => new User()));

    assertType('Illuminate\Database\Eloquent\Collection<string, App\User>', $users->mapWithKeys(fn ($user) => [$user->name => $user]));
    assertType('Illuminate\Database\Eloquent\Collection<int|string, App\User>', $users->mapWithKeys(fn ($user) => [$user->name => $user, 5 => $user]));
    assertType('App\TransactionCollection<int, App\Transaction>', $transactions->mapWithKeys(fn ($transaction) => [$transaction->id => $transaction]));
    assertType('App\TransactionCollection<string, App\User>', $transactions->mapWithKeys(fn ($transaction) => ['user' => new User()]));
    assertType('App\UserCollection', $userCollection->mapWithKeys(fn ($user) => [$user->name => $user]));
    assertType('App\OnlyValueGenericCollection<App\ModelWithOnlyValueGenericCollection>', $onlyValue->mapWithKeys(fn ($model) => ['a' => $model]));
}

/**
 * One value that is not a model turns the whole result into a base collection,
 * so a callback that might return either cannot promise an Eloquent collection.
 *
 * @param EloquentCollection<int, User>                                   $users
 * @param EloquentCollection<string, User>                                $keyedUsers
 * @param TransactionCollection<int, Transaction>                         $transactions
 * @param OnlyValueGenericCollection<ModelWithOnlyValueGenericCollection> $onlyValue
 * @param EloquentCollection<int, User>|null                              $nullableUsers
 * @param callable(User): (User|null)                                     $nullableUserCallback
 */
function mightReturnModel(
    EloquentCollection $users,
    EloquentCollection $keyedUsers,
    TransactionCollection $transactions,
    OnlyValueGenericCollection $onlyValue,
    EloquentCollection|null $nullableUsers,
    callable $callback,
    callable $nullableUserCallback,
): void {
    assertType('Illuminate\Support\Collection<int, App\User|null>', $users->map(fn (User $user): ?User => $user->id > 5 ? $user : null));
    assertType('Illuminate\Support\Collection<int, App\User|null>', $users->map(fn ($user) => $user->id > 5 ? $user : null));
    assertType('Illuminate\Support\Collection<int, App\Group|null>', $users->map(fn ($user) => $user->group));
    assertType('Illuminate\Support\Collection<int, App\User|string>', $users->map(fn ($user) => $user->id > 5 ? $user : $user->name));
    assertType('Illuminate\Support\Collection<int, mixed>', $users->map($callback));
    assertType('Illuminate\Support\Collection<int, App\User|null>', $users->map($nullableUserCallback));
    assertType('Illuminate\Support\Collection<string, App\User|null>', $keyedUsers->map(fn ($user) => $user->id > 5 ? $user : null));
    assertType('Illuminate\Support\Collection<int, App\Transaction|null>', $transactions->map(fn ($transaction) => $transaction->id > 5 ? $transaction : null));
    assertType('Illuminate\Support\Collection<int, App\ModelWithOnlyValueGenericCollection|null>', $onlyValue->map(fn ($model) => $model->exists ? $model : null));
    assertType('Illuminate\Support\Collection<int, App\User|null>|null', $nullableUsers?->map(fn ($user) => $user->id > 5 ? $user : null));

    assertType('Illuminate\Support\Collection<string, App\User|null>', $users->mapWithKeys(fn ($user) => [$user->name => $user->id > 5 ? $user : null]));
    assertType('Illuminate\Support\Collection<int, App\Transaction|null>', $transactions->mapWithKeys(fn ($transaction) => [$transaction->id => $transaction->id > 5 ? $transaction : null]));

    // The result is a base collection even after the values that are not models are removed.
    assertType('Illuminate\Support\Collection<int, App\User>', $users->map(fn ($user) => $user->id > 5 ? $user : null)->filter());
}

/**
 * @param EloquentCollection<int, User>                                  $users
 * @param EloquentCollection<int, User>|EloquentCollection<int, Account> $usersOrAccounts
 * @param EloquentCollection<int, User>|SupportCollection<int, int>      $usersOrIntegers
 * @param EloquentCollection<int, User>|LazyCollection<string, User>     $usersOrLazyUsers
 */
function callablesAndReceivers(
    EloquentCollection $users,
    EloquentCollection $usersOrAccounts,
    EloquentCollection|SupportCollection $usersOrIntegers,
    EloquentCollection|LazyCollection $usersOrLazyUsers,
): void {
    assertType('Illuminate\Support\Collection<int, int>', $users->map(UserMapper::toId(...)));
    assertType('Illuminate\Support\Collection<int, int>', $users->map([UserMapper::class, 'toId']));
    assertType('Illuminate\Support\Collection<int, App\User|null>', $users->map(UserMapper::toNullableUser(...)));
    assertType('Illuminate\Support\Collection<int, string>', $users->map(new InvokableUserMapper()));
    assertType('Illuminate\Support\Collection<int, int<0, max>>', $users->map(callback: fn ($user) => $user->id));
    assertType('Illuminate\Support\Collection<int, App\User|null>', $users->map(callback: fn ($user) => $user->id > 5 ? $user : null));

    assertType('Illuminate\Database\Eloquent\Collection<int, App\Account|App\User>', $usersOrAccounts->map(fn ($model) => $model));
    assertType('Illuminate\Support\Collection<int, App\Account|App\User|null>', $usersOrAccounts->map(fn ($model) => $model->exists ? $model : null));
    assertType('Illuminate\Support\Collection<int, string>', $usersOrIntegers->map(fn ($value) => 'foo'));
    assertType('Illuminate\Support\Collection<int, App\User|int>', $usersOrIntegers->map(fn ($value) => $value));
    assertType('Illuminate\Support\Collection<int, App\User|null>|Illuminate\Support\LazyCollection<string, App\User|null>', $usersOrLazyUsers->map(fn ($user) => $user->exists ? $user : null));
}

/**
 * Laravel keeps the collection class when every mapped value is a model, whatever model that is. A class that pins
 * its key or model type would stay in the result while claiming to hold its own model, so it is left out.
 *
 * @param OnlyValueGenericCollection<ModelWithOnlyValueGenericCollection> $pinnedKey
 * @param PinnedModelCollection<int>                                      $pinnedModel
 */
function collectionsThatPinTheirTypes(
    UserCollection $pinnedKeyAndModel,
    OnlyValueGenericCollection $pinnedKey,
    PinnedModelCollection $pinnedModel,
): void {
    assertType('Illuminate\Support\Collection<int, App\User|null>', $pinnedKeyAndModel->map(fn ($user) => $user->exists ? $user : null));
    assertType('Illuminate\Support\Collection<int, App\Group|null>', $pinnedKeyAndModel->map(fn ($user) => $user->group));
    assertType('App\Group|null', $pinnedKeyAndModel->map(fn ($user) => $user->group)->first());
    assertType('Illuminate\Support\Collection<int, App\Account|null>', $pinnedKeyAndModel->map(fn ($user) => $user->id > 5 ? new Account() : null));
    assertType('Illuminate\Support\Collection<string, App\Account|null>', $pinnedKeyAndModel->mapWithKeys(fn ($user) => [$user->name => $user->id > 5 ? new Account() : null]));

    assertType('Illuminate\Support\Collection<string, App\ModelWithOnlyValueGenericCollection|null>', $pinnedKey->mapWithKeys(fn ($model) => ['a' => $model->exists ? $model : null]));
    assertType('Illuminate\Support\Collection<int, App\Group|null>', $pinnedModel->map(fn ($user) => $user->group));
}

/**
 * @template TKey of array-key
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends EloquentCollection<TKey, TModel>
 */
class InsideCollection extends EloquentCollection
{
    public function test(): void
    {
        assertType('static(EloquentCollectionMap\InsideCollection<TKey of (int|string) (class EloquentCollectionMap\InsideCollection, argument), TModel of Illuminate\Database\Eloquent\Model (class EloquentCollectionMap\InsideCollection, argument)>)', $this->map(fn ($model) => $model));
        assertType('Illuminate\Support\Collection<TKey of (int|string) (class EloquentCollectionMap\InsideCollection, argument), bool>', $this->map(fn ($model) => $model->exists));
        assertType('Illuminate\Support\Collection<TKey of (int|string) (class EloquentCollectionMap\InsideCollection, argument), TModel of Illuminate\Database\Eloquent\Model (class EloquentCollectionMap\InsideCollection, argument)|null>', $this->map(fn ($model) => $model->exists ? $model : null));
    }
}

/**
 * @template TKey of array-key
 *
 * @extends EloquentCollection<TKey, User>
 */
class PinnedModelCollection extends EloquentCollection
{
}

/** @extends EloquentCollection<int, User> */
class OverridesMapCollection extends EloquentCollection
{
    /**
     * @param callable(User, int): mixed $callback
     *
     * @return $this
     */
    public function map(callable $callback): static
    {
        return $this->each($callback);
    }
}

function overriddenMethod(OverridesMapCollection $collection): void
{
    assertType('EloquentCollectionMap\OverridesMapCollection', $collection->map(fn ($user) => $user->id > 5 ? $user : null));
    assertType('Illuminate\Support\Collection<string, App\User|null>', $collection->mapWithKeys(fn ($user) => [$user->name => $user->id > 5 ? $user : null]));
}

class UserMapper
{
    public static function toId(User $user): int
    {
        return $user->id;
    }

    public static function toNullableUser(User $user): User|null
    {
        return $user->id > 5 ? $user : null;
    }
}

class InvokableUserMapper
{
    public function __invoke(User $user): string
    {
        return $user->name;
    }
}
