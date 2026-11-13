<?php

use App\User;

/** @var User $user */
$user->accounts()->forceCreateMany([['foo' => 'bar']]);
$user->accounts()->forceCreateManyQuietly([['foo' => 'bar']]);
$user->address()->forceCreateMany([['foo' => 'bar']]);

$user->accounts()->forceCreateMany([['name' => 'foo']]);
$user->accounts()->forceCreateManyQuietly([['name' => 'foo']]);
$user->address()->forceCreateMany([]);

/** @param array<int, array{name: string}> $records */
function forceCreateManyWithIntKeys(User $user, array $records): void
{
    $user->accounts()->forceCreateMany($records);
}

/** @param array<int, array{foo: string}> $records */
function forceCreateManyInvalidWithIntKeys(User $user, array $records): void
{
    $user->accounts()->forceCreateMany($records);
}
