<?php

namespace CollectionWhere;

use App\Account;
use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;

use function PHPStan\Testing\assertType;

function convertToAccount(User $user): ?Account
{ }

function dummyFilter($value)
{
    if ($value instanceof User) {
        return true;
    }

    return random_int(0, 1) > 1;
}

/**
 * @param EloquentCollection<int, User> $users
 * @param SupportCollection<array-key, mixed> $mixedCollection
 */
function test(User $user, SupportCollection $users, SupportCollection $mixedCollection): void
{
    assertType("Illuminate\Support\Collection<(int|string), mixed>", collect()->where('foo', '<>', 1));

    assertType('Illuminate\Support\Collection<int, int<3, max>>', collect([1, 2, 3, 4, 5, 6])->where(function (int $value) {
        return $value > 2;
    }));
    assertType('Illuminate\Support\Collection<int, int<3, max>>', collect([1, 2, 3, 4, 5, 6])->where(fn (int $value) => $value > 2));

    assertType("Illuminate\Database\Eloquent\Collection<int, App\User>", $users->where(function (User $user): bool {
        return ! $user->blocked;
    }));
    assertType("Illuminate\Database\Eloquent\Collection<int, App\User>", $users->where(fn (User $user) => ! $user->blocked));

    assertType(
        'Illuminate\Support\Collection<int, App\Account>',
        collect($users->all())
        ->map(function (User $attachment): ?Account {
            return convertToAccount($attachment);
        })
        ->where(fn($user) => $user)
    );

    $accounts = $user->accounts()->active()->get();
    assertType('App\AccountCollection<int, App\Account>', $accounts);

    assertType('App\AccountCollection<int, App\Account>', $accounts->where(function ($account) {
        return \CollectionStubs\dummyFilter($account);
    }));

    $accounts->where(function ($account) {
        return dummyFilter($account);
    })
    ->map(function ($account) {
        assertType('App\Account', $account);
    });
}
