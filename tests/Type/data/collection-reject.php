<?php

namespace CollectionReject;

use App\Account;
use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;

use function PHPStan\Testing\assertType;

function convertToAccount(User $user): ?Account
{ }

function dummyReject($value)
{
    if ($value instanceof User) {
        return true;
    }

    return random_int(0, 1) > 1;
}

/** @param EloquentCollection<int, User> $users */
function test(User $user, SupportCollection $users): void
{
    assertType("Illuminate\Support\Collection<(int|string), 0|0.0|''|'0'|array{}|false|null>", collect()->reject());

    assertType("Illuminate\Support\Collection<int, ''|'0'|null>", collect(['foo', null, '', 'bar', null])->reject());

    assertType('Illuminate\Support\Collection<int, int<min, 2>>', collect([1, 2, 3, 4, 5, 6])->reject(function (int $value) {
        return $value > 2;
    }));
    assertType('Illuminate\Support\Collection<int, int<min, 2>>', collect([1, 2, 3, 4, 5, 6])->reject(fn (int $value) => $value > 2));

    assertType("Illuminate\Database\Eloquent\Collection<int, App\User>", $users->reject(function (User $user): bool {
        return ! $user->blocked;
    }));
    assertType("Illuminate\Database\Eloquent\Collection<int, App\User>", $users->reject(fn (User $user) => ! $user->blocked));

    assertType(
        'Illuminate\Support\Collection<int, null>',
        collect($users->all())
        ->map(function (User $attachment): ?Account {
            return convertToAccount($attachment);
        })
        ->reject()
    );

    $accounts = $user->accounts()->active()->get();
    assertType('App\AccountCollection<int, App\Account>', $accounts);

    assertType('App\AccountCollection<int, App\Account>', $accounts->reject(function ($account) {
        return \CollectionStubs\dummyReject($account);
    }));

    $accounts->reject(function ($account) {
        return dummyReject($account);
    })
    ->map(function ($account) {
        assertType('App\Account', $account);
    });
}
