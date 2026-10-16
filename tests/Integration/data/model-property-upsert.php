<?php

use App\User;
use Illuminate\Support\Facades\DB;

User::query()->upsert(['foo' => 'bar'], 'email');
User::query()->upsert([['email' => 'taylor@example.com', 'foo' => 'bar']], 'email');
User::query()->upsert(['email' => 'taylor@example.com'], 'foo');
User::query()->upsert(['email' => 'taylor@example.com'], ['email', 'foo']);
User::query()->upsert(['email' => 'taylor@example.com'], 'email', ['foo' => DB::raw('1')]);

User::query()->upsert(['email' => 'taylor@example.com', 'name' => 'Taylor'], 'email');
User::query()->upsert([['email' => 'taylor@example.com', 'name' => 'Taylor'], ['email' => 'abigail@example.com', 'name' => 'Abigail']], ['email'], ['name']);
User::query()->upsert(['email' => 'taylor@example.com', 'name' => 'Taylor'], 'email', ['name' => DB::raw('upper(name)')]);
User::query()->upsert(['email' => 'taylor@example.com', 'email_verified_at' => now()], 'email', null);
User::query()->upsert([], 'email');

User::upsert(['foo' => 'bar'], 'email');
User::upsert(['email' => 'taylor@example.com', 'name' => 'Taylor'], 'email', ['name']);

/** @var User $user */
$user->accounts()->upsert(['foo' => 'bar'], 'name');
$user->accounts()->upsert([['name' => 'foo', 'foo' => 'bar']], 'name');
$user->accounts()->upsert(['name' => 'foo'], 'foo');
$user->accounts()->upsert([['name' => 'foo', 'active' => '1']], ['name'], ['active']);

/** @param array<int, array{email: string, name: string}> $rows */
function upsertRowsWithIntKeys(array $rows): void
{
    User::query()->upsert($rows, 'email');
}

/** @param array<int, array{email: string, foo: string}> $rows */
function upsertInvalidRowsWithIntKeys(array $rows): void
{
    User::query()->upsert($rows, 'email');
}
