<?php

declare(strict_types=1);

namespace QueryBuilder;

use Illuminate\Support\Facades\DB;

use function PHPStan\Testing\assertType;

function test(): void
{
    $record = DB::table('user')->pluck('email', 'id');
    assertType('Illuminate\Support\Collection<(int|string), mixed>', $record);
    assertType('Illuminate\Support\LazyCollection<int, stdClass>', DB::table('user')->cursor());

    $subQuery = DB::table('addresses')
        ->select(['id', 'user_id']);

    $builder = DB::table('users')
        ->joinSub($subQuery, 'addresses', 'users.user_id', '=', DB::raw('addresses.user_id'));

    assertType('Illuminate\Database\Query\Builder', $builder);

    $builder = DB::table('users')
        ->where(DB::raw('id'), '=', 1);

    assertType('Illuminate\Database\Query\Builder', $builder);

    $builder = DB::table('users')
        ->where(DB::raw('id'), '=', 1)
        ->orWhere(DB::raw('id'), '=', 2);

    assertType('Illuminate\Database\Query\Builder', $builder);

    $builder = DB::table('users')
        ->whereNull(DB::raw('email_verified_at'));

    assertType('Illuminate\Database\Query\Builder', $builder);

    $builder = DB::table('users')
        ->whereNotNull(DB::raw('email_verified_at'));

    assertType('Illuminate\Database\Query\Builder', $builder);

    $builder = DB::table('users')
        ->whereBetween('id', new \ArrayObject([1, 2]));

    assertType('Illuminate\Database\Query\Builder', $builder);

    assertType('array<mixed>|object|null', DB::table('users')->find(1, [DB::raw('email_verified_at')]));
    assertType('mixed', DB::table('users')->aggregate('sum', [DB::raw('id')]));
    assertType('float|int', DB::table('users')->numericAggregate('sum', [DB::raw('id')]));
    assertType('float|int|numeric-string', DB::table('users')->sum(DB::raw('id')));
    assertType('float|int|numeric-string|null', DB::table('users')->avg(DB::raw('id')));
    assertType('float|int|numeric-string|null', DB::table('users')->average(DB::raw('id')));
    assertType('array{select: list<mixed>, from: list<mixed>, join: list<mixed>, where: list<mixed>, groupBy: list<mixed>, having: list<mixed>, order: list<mixed>, union: list<mixed>, unionOrder: list<mixed>}', DB::table('users')->getRawBindings());
}
