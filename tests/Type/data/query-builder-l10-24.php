<?php

declare(strict_types=1);

namespace QueryBuilder;

use Illuminate\Support\Facades\DB;

use function PHPStan\Testing\assertType;

function testJoinSubExpressionParameter(): void
{
    $subQuery = DB::table('addresses')
        ->select(['id', 'user_id']);

    $builder = DB::table('users')
        ->joinSub($subQuery, 'addresses', 'users.user_id', '=', DB::raw('addresses.user_id'));

    assertType('Illuminate\Database\Query\Builder', $builder);
}

function testWhereExpressionParameter(): void
{
    $builder = DB::table('users')
        ->where(DB::raw('id'), '=', 1);

    assertType('Illuminate\Database\Query\Builder', $builder);
}

function testOrWhereExpressionParameter(): void
{
    $builder = DB::table('users')
        ->where(DB::raw('id'), '=', 1)
        ->orWhere(DB::raw('id'), '=', 2);

    assertType('Illuminate\Database\Query\Builder', $builder);
}

function testWhereNullExpressionParameter(): void
{
    $builder = DB::table('users')
        ->whereNull(DB::raw('email_verified_at'));

    assertType('Illuminate\Database\Query\Builder', $builder);
}

function testWhereNotNullExpressionParameter(): void
{
    $builder = DB::table('users')
        ->whereNotNull(DB::raw('email_verified_at'));

    assertType('Illuminate\Database\Query\Builder', $builder);
}
