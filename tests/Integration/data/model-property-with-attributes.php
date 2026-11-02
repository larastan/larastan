<?php

use App\User;
use Illuminate\Support\Facades\DB;

User::query()->withAttributes(['foo' => 'bar']);
User::query()->withAttributes('foo', 'bar');

User::query()->withAttributes(['email' => 'taylor@example.com', 'name' => 'Taylor']);
User::query()->withAttributes('email', 'taylor@example.com');
User::query()->withAttributes(DB::raw('email'), 'taylor@example.com');
User::query()->where('name', 'Taylor')->withAttributes('email', 'taylor@example.com')->create();

User::withAttributes(['foo' => 'bar']);
User::withAttributes(['email' => 'taylor@example.com']);

/** @var User $user */
$user->accounts()->withAttributes(['foo' => 'bar']);
$user->accounts()->withAttributes('foo', 'bar');
$user->accounts()->withAttributes(['name' => 'foo']);

/** @param array<string, string> $attributes */
function withAttributesFromStringKeys(array $attributes): void
{
    User::query()->withAttributes($attributes);
}
