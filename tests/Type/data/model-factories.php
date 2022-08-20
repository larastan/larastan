<?php

namespace ModelFactories;

use App\User;
use function PHPStan\Testing\assertType;

assertType('Database\Factories\UserFactory', User::factory());
assertType('Database\Factories\UserFactory', User::factory()->new());
assertType('App\User', User::factory()->createOne());
assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory()->createMany([]));
assertType('App\User', User::factory()->makeOne());

assertType('App\User', User::factory()->create());
assertType('App\User', User::factory()->createQuietly());
assertType('App\User', User::factory()->make());

assertType('App\User', User::factory()->configure()->create());
assertType('App\User', User::factory()->configure()->createQuietly());
assertType('App\User', User::factory()->configure()->make());

assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory(2)->create());
assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory(2)->createQuietly());
assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory(2)->make());

assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory()->count(1)->create());
assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory()->count(1)->createQuietly());
assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory()->count(1)->make());

assertType('App\User', User::factory(2)->count(null)->create());
assertType('App\User', User::factory(2)->count(null)->createQuietly());
assertType('App\User', User::factory(2)->count(null)->make());

assertType('App\User', User::factory(2)->count(null)->create());
assertType('App\User', User::factory(2)->count(null)->createQuietly());
assertType('App\User', User::factory(2)->count(null)->make());

assertType('App\User', User::factory(2)->state(['foo'])->count(null)->create());
assertType('App\User', User::factory(2)->state(['foo'])->count(null)->createQuietly());
assertType('App\User', User::factory(2)->state(['foo'])->count(null)->make());

function foo(?int $foo): void
{
    assertType('App\User|Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory()->count($foo)->create());
    assertType('App\User|Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory()->count($foo)->createQuietly());
    assertType('App\User|Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory()->count($foo)->make());

    assertType('App\User|Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory($foo)->create());
    assertType('App\User|Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory($foo)->createQuietly());
    assertType('App\User|Illuminate\Database\Eloquent\Collection<int, App\User>', User::factory($foo)->make());
}
