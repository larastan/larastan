<?php

namespace CollectionMethodParameterClosureTypeExtension;

use App\Account;
use App\AccountCollection;
use App\User;
use Illuminate\Database\Eloquent\Collection;
use function PHPStan\Testing\assertType;

function test(): void
{
    Account::query()->chunk(100, function (AccountCollection $accounts) {
        assertType('App\AccountCollection<int, App\Account>', $accounts);
    });

    User::query()->chunk(100, function (Collection $users) {
        assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $users);
    });

    Account::query()->chunk(100, function ($accounts) {
        assertType('App\AccountCollection<int, App\Account>', $accounts);
    });

    User::query()->chunk(100, function ($users) {
        assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $users);
    });

    Account::query()->chunkById(100, function (AccountCollection $accounts, int $page) {
        assertType('App\AccountCollection<int, App\Account>', $accounts);
        assertType('int<1, max>', $page);
    });

    User::query()->chunkById(100, function (Collection $users, int $page) {
        assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $users);
        assertType('int<1, max>', $page);
    });

    Account::query()->chunkById(100, function ($accounts, $page) {
        assertType('App\AccountCollection<int, App\Account>', $accounts);
        assertType('int<1, max>', $page);
    });

    User::query()->chunkById(100, function ($users, $page) {
        assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $users);
        assertType('int<1, max>', $page);
    });
}
