<?php

namespace CollectionFilter;

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

/** @param EloquentCollection<int, User> $users */
function test(User $user, SupportCollection $users): void
{
    assertType("Illuminate\Support\Collection<(int|string), mixed~(0|0.0|''|'0'|array{}|false|null)>", collect()->filter());

    assertType('Illuminate\Support\Collection<int, non-falsy-string>', collect(['foo', null, '', 'bar', null])->filter());

    assertType('Illuminate\Support\Collection<int, int<3, max>>', collect([1, 2, 3, 4, 5, 6])->filter(function (int $value) {
        return $value > 2;
    }));
    assertType('Illuminate\Support\Collection<int, int<3, max>>', collect([1, 2, 3, 4, 5, 6])->filter(fn (int $value) => $value > 2));

    assertType("Illuminate\Database\Eloquent\Collection<int, App\User>", $users->filter(function (User $user): bool {
        return ! $user->blocked;
    }));
    assertType("Illuminate\Database\Eloquent\Collection<int, App\User>", $users->filter(fn (User $user) => ! $user->blocked));

    assertType(
        'Illuminate\Support\Collection<int, App\Account>',
        collect($users->all())
        ->map(function (User $attachment): ?Account {
            return convertToAccount($attachment);
        })
        ->filter()
    );

    $accounts = $user->accounts()->active()->get();
    assertType('App\AccountCollection<int, App\Account>', $accounts);

    assertType('App\AccountCollection<int, App\Account>', $accounts->filter(function ($account) {
        return \CollectionStubs\dummyFilter($account);
    }));

    $accounts->filter(function ($account) {
        return dummyFilter($account);
    })
    ->map(function ($account) {
        assertType('App\Account', $account);
    });
}
