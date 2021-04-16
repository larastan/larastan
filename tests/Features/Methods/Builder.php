<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use App\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
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

    public function testWhereNotBetweenInt(): QueryBuilder
    {
        return User::query()
            ->whereNotBetween('a', [1, 5])
            ->orWhereNotBetween('a', [1, 5])
            ->toBase();
    }

    /** @phpstan-return EloquentBuilder<User> */
    public function testWithTrashedOnBuilderWithModel(): EloquentBuilder
    {
        return User::query()->withTrashed();
    }

    public function testOrderByToBaseWithQueryExpression(): ?QueryBuilder
    {
        return User::whereNull('name')
            ->orderBy(\Illuminate\Support\Facades\DB::raw('name'))
            ->toBase();
    }

    public function testLatestToBaseWithQueryExpression(): ?QueryBuilder
    {
        return User::whereNull('name')
            ->latest(\Illuminate\Support\Facades\DB::raw('created_at'))
            ->toBase();
    }

    public function testOldestToBaseWithQueryExpression(): ?QueryBuilder
    {
        return User::whereNull('name')
            ->oldest(\Illuminate\Support\Facades\DB::raw('created_at'))
            ->toBase();
    }

    public function testPluckToBaseWithQueryExpression(): ?Collection
    {
        return User::whereNull('name')
            ->pluck(\Illuminate\Support\Facades\DB::raw('created_at'))
            ->toBase();
    }

    public function testIncrementWithQueryExpression(): int
    {
        /** @var User $user */
        $user = new User;

        return $user->increment(\Illuminate\Support\Facades\DB::raw('counter'));
    }

    public function testDecrementWithQueryExpression(): int
    {
        /** @var User $user */
        $user = new User;

        return $user->decrement(\Illuminate\Support\Facades\DB::raw('counter'));
    }

    /** @phpstan-return null|EloquentBuilder<User> */
    public function testWhen(bool $foo): ?EloquentBuilder
    {
        $innerQuery = null;
        User::query()->when($foo, static function (EloquentBuilder $query) use (&$innerQuery) {
            /** @phpstan-var EloquentBuilder<User> $query */
            $innerQuery = $query;

            return $query->whereNull('name');
        });

        return $innerQuery;
    }

    public function testWhenVoid(bool $foo): void
    {
        User::query()->when($foo, static function (EloquentBuilder $query) {
            $query->whereNull('name');
        });
    }

    /** @phpstan-return null|EloquentBuilder<User> */
    public function testUnless(bool $foo): ?EloquentBuilder
    {
        $innerQuery = null;
        User::query()->unless($foo, static function (EloquentBuilder $query) use (&$innerQuery) {
            /** @phpstan-var EloquentBuilder<User> $query */
            $innerQuery = $query;

            return $query->whereNull('name');
        });

        return $innerQuery;
    }

    public function testUnlessVoid(bool $foo): void
    {
        User::query()->unless($foo, static function (EloquentBuilder $query) {
            $query->whereNull('name');
        });
    }
}
