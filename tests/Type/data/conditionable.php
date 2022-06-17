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

assertType('ConditionableStubs\Foo', (new Foo())->when(true, function (Foo $foo) {
    // do nothing
}));

assertType('ConditionableStubs\Foo', (new Foo())->when(true, function (Foo $foo) {
    return null;
}));

assertType('int', (new Foo())->when(true, function (Foo $foo): int {
    return rand();
}));

assertType('ConditionableStubs\Foo', (new Foo())->unless(true, function (Foo $foo) {
    // do nothing
}));

assertType('ConditionableStubs\Foo', (new Foo())->unless(true, function (Foo $foo) {
    return null;
}));

assertType('int', (new Foo())->unless(true, function (Foo $foo): int {
    return rand();
}));

/** @param Builder<User> $query */
function doFoo(Builder $query): void
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query->when(true, static function (Builder $query): Builder {
        /** @phpstan-var Builder<User> $query */

        return $query->whereNull('name');
    }));
}
