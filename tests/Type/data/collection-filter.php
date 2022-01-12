<?php

namespace CollectionFilter;

use App\Account;
use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use function PHPStan\Testing\assertType;

/** @var User $user */
assertType('Illuminate\Support\Collection<int, non-empty-string>', collect(['foo', null, '', 'bar', null])->filter());

/** @param Collection<int, mixed> $foo */
function foo(Collection $foo): void
{
    assertType("Illuminate\Support\Collection<int, mixed~0|0.0|''|'0'|array{}|false|null>", $foo->filter());
}

/**
 * @param  array<int, User>  $attachments
 */
function storeAttachments(array $attachments): void
{
    assertType(
        'Illuminate\Support\Collection<int, App\Account>',
        collect($attachments)
        ->map(function (User $attachment): ?Account {
            return convertToAccount($attachment);
        })
        ->filter()
    );
}

function convertToAccount(User $user): ?Account
{
    //
}

assertType('Illuminate\Support\Collection<int, int<3, max>>', collect([1, 2, 3, 4, 5, 6])->filter(function (int $value) {
    return $value > 2;
}));

/** @param EloquentCollection<int, User> $foo */
function bar(Collection $foo): void
{
    assertType("Illuminate\Database\Eloquent\Collection<int, App\User>", $foo->filter(function (User $user): bool {
        return ! $user->blocked;
    }));
}

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

function dummyFilter($value)
{
    if ($value instanceof User) {
        return true;
    }

    return random_int(0, 1) > 1;
}
