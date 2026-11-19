<?php

namespace QueryBuilderMethods;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

use function PHPStan\Testing\assertType;

function test(Builder $query, JoinClause $join): void
{
    // Dynamic wheres return the builder they were called on.
    assertType('Illuminate\Database\Query\Builder', $query->whereName('foo'));
    assertType('Illuminate\Database\Query\Builder', $query->whereNameAndEmail('foo', 'bar')->whereActive(true));
    assertType('Illuminate\Database\Query\Builder', DB::table('users')->whereName('foo'));
    assertType('Illuminate\Database\Query\JoinClause', $join->whereName('foo'));

    assertType('*ERROR*', $query->doesNotExist());

    // Macros take precedence, on instances and statically.
    assertType('Illuminate\Database\Query\Builder', $query->queryBuilderMacro());
    assertType('Illuminate\Database\Query\JoinClause', $join->queryBuilderMacro());
    assertType('string', Builder::globalCustomDatabaseQueryMacro(b: 99));
}
