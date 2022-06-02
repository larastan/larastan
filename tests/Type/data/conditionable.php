<?php

namespace ConditionableStubs;

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
