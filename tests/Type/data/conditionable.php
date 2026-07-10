<?php

namespace ConditionableStubs;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Traits\Conditionable;

use function PHPStan\Testing\assertType;

class Foo
{
    use Conditionable;
}

/** @param Builder<User> $query */
function test(Foo $foo, User $user, Builder $query): void
{
    assertType('ConditionableStubs\Foo', $foo->when(true, function (Foo $foo) {
        // do nothing
    }));

    assertType('ConditionableStubs\Foo', $foo->when(true, function (Foo $foo) {
        return null;
    }));

    assertType('int<0, max>', $foo->when(true, function (Foo $foo): int {
        return rand();
    }));

    // Test to make sure the callback has a non-null value.
    $foo->when(User::first(), function (Foo $foo, $user): void {
        assertType(User::class, $user);
    });

    assertType('ConditionableStubs\Foo', $foo->unless(true, function (Foo $foo) {
        // do nothing
    }));

    assertType('ConditionableStubs\Foo', $foo->unless(true, function (Foo $foo) {
        return null;
    }));

    assertType('int<0, max>', $foo->unless(true, function (Foo $foo): int {
        return rand();
    }));

    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query->when(true, static function (Builder $query): Builder {
        /** @phpstan-var Builder<User> $query */
        return $query->whereNull('name');
    }));

    // when()/unless() on relations should return the relation type, not the builder type
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', $user->accounts()->when(true, fn ($q) => $q));
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', $user->accounts()->unless(false, fn ($q) => $q));
    assertType("Illuminate\Database\Eloquent\Relations\BelongsToMany<App\Role, App\User, Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>", $user->roles()->when(true, fn ($q) => $q));

    // when() on a relation with a custom builder model should still return the relation type
    assertType("Illuminate\Database\Eloquent\Relations\BelongsToMany<App\Post, App\User, Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>", $user->posts()->when(true, fn ($q) => $q));
}
