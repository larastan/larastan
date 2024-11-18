<?php

namespace Bug2074;

use Illuminate\Database\Eloquent\Builder;

use function PHPStan\Testing\assertType;

/** @param Builder<*> $query */
function test(Builder $query): void
{
    assertType('Illuminate\Database\Eloquent\Builder<*>', $query->where('foo', 'bar'));
}
