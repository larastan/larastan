<?php

use App\User;

User::query()->fillAndInsert([['email' => 'taylor@example.com', 'foo' => 'bar']]);
User::query()->fillAndInsertOrIgnore([['email' => 'taylor@example.com', 'foo' => 'bar']]);
User::query()->fillAndInsertGetId(['email' => 'taylor@example.com', 'foo' => 'bar']);
User::query()->fillForInsert([['email' => 'taylor@example.com', 'foo' => 'bar']]);

User::query()->fillAndInsert([['email' => 'taylor@example.com', 'name' => 'Taylor'], ['email' => 'abigail@example.com', 'name' => 'Abigail']]);
User::query()->fillAndInsertOrIgnore([['email' => 'taylor@example.com', 'name' => 'Taylor']]);
User::query()->fillAndInsertGetId(['email' => 'taylor@example.com', 'name' => 'Taylor']);
User::query()->fillForInsert([['email' => 'taylor@example.com', 'name' => 'Taylor']]);
User::query()->fillAndInsert([]);

User::fillAndInsert([['email' => 'taylor@example.com', 'foo' => 'bar']]);
User::fillAndInsertGetId(['email' => 'taylor@example.com', 'name' => 'Taylor']);

/** @param array<int, array{email: string, name: string}> $rows */
function fillAndInsertRowsWithIntKeys(array $rows): void
{
    User::query()->fillAndInsert($rows);
    User::query()->fillForInsert($rows);
}

/** @param array<int, array{email: string, foo: string}> $rows */
function fillAndInsertInvalidRowsWithIntKeys(array $rows): void
{
    User::query()->fillAndInsert($rows);
}

function fillAndInsertThroughRelation(User $user): void
{
    $user->accounts()->fillAndInsert([['name' => 'foo', 'foo' => 'bar']]);
}
