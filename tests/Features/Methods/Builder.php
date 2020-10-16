<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use App\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use stdClass;

class Builder
{
    public function testGroupBy(): EloquentBuilder
    {
        return User::query()->groupBy('foo', 'bar');
    }

    public function testDynamicWhereAsString(): ?User
    {
        return (new User())->whereEmail('bar')->first();
    }

    public function testDynamicWhereMultiple(): ?User
    {
        return User::whereIdAndEmail(1, 'foo@example.com')->first();
    }

    public function testDynamicWhereAsInt(): ?User
    {
        return (new User())->whereEmail(1)->first();
    }

    public function testGetQueryReturnsQueryBuilder(): QueryBuilder
    {
        return User::getQuery();
    }

    public function testToBaseReturnsQueryBuilder(): QueryBuilder
    {
        return User::toBase();
    }

    public function testToBaseReturnsQueryBuilderAfterChain(): QueryBuilder
    {
        return User::whereNull('name')
            ->orderBy('email')
            ->toBase();
    }

    public function testQueryBuilderChainStartedWithGetQueryReturnsStdClass(): ?stdClass
    {
        return User::getQuery()
        ->select('some_model.created')
        ->where('some_model.some_column', '=', true)
        ->orderBy('some_model.created', 'desc')
        ->first();
    }
}
