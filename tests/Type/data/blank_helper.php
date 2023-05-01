<?php

namespace BlankHelper;

use Illuminate\Support\Collection;
use stdClass;

use function PHPStan\Testing\assertType;

function asString(?string $foo)
{
    if (blank($foo)) {
        assertType("''|' '|null", $foo);

        return;
    }
    assertType('non-empty-string', $foo);
}

/**
 * @param  array<int>  $foo
 */
function asArray(?array $foo)
{
    if (blank($foo)) {
        assertType('array{}|null', $foo);

        return;
    }
    assertType('non-empty-array<int>', $foo);
}

function asInt(?int $foo)
{
    if (blank($foo)) {
        assertType('null', $foo);

        return;
    }
    assertType('int', $foo);
}

function asBooleanWithNull(?bool $foo)
{
    if (blank($foo)) {
        assertType('false|null', $foo);

        return;
    }
    assertType('true', $foo);
}

function asBoolean(bool $foo)
{
    if (blank($foo)) {
        assertType('false', $foo);

        return;
    }
    assertType('true', $foo);
}

/**
 * @param  Collection<int, int>  $foo
 */
function asCollectionWithType(Collection $foo)
{
    if (blank($foo)) {
        assertType("Illuminate\Support\Collection<int, int>", $foo);

        return;
    }
    assertType("Illuminate\Support\Collection<int, int>", $foo);
}

/**
 * @param  Collection<int, int>|null  $foo
 */
function asCollectionWithNull(?Collection $foo)
{
    if (blank($foo)) {
        assertType("Illuminate\Support\Collection<int, int>|null", $foo);

        return;
    }

    assertType("Illuminate\Support\Collection<int, int>", $foo);
}

function asStdClass(?stdClass $foo)
{
    if (blank($foo)) {
        assertType('null', $foo);

        return;
    }
    assertType('stdClass', $foo);
}

function asIterable(?iterable $foo)
{
    if (blank($foo)) {
        assertType('iterable|null', $foo);

        return;
    }
    assertType('iterable', $foo);
}
