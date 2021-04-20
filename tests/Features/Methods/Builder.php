<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use App\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use stdClass;

/**
 * This class tests `EloquentBuilder::__call` method.
 *
 * So every test should either begin with `User::query`,
 * or need to use a `Builder` typed argument.
 *
 * Return type should always be checked with `@return` annotation.
 */
class Builder
{
    /** @phpstan-return EloquentBuilder<User> */
    public function testGroupBy(): EloquentBuilder
    {
        return User::query()->groupBy('foo', 'bar');
    }

    /** @phpstan-return EloquentBuilder<User> */
    public function testDynamicWhereAsString(): EloquentBuilder
    {
        return User::query()->whereEmail('bar');
    }

    /** @phpstan-return EloquentBuilder<User> */
    public function testDynamicWhereMultiple(): EloquentBuilder
    {
        return User::query()->whereIdAndEmail(1, 'foo@example.com');
    }

    /** @phpstan-return EloquentBuilder<User> */
    public function testDynamicWhereAsInt(): EloquentBuilder
    {
        return User::query()->whereEmail(1);
    }

    public function testToBaseReturnsQueryBuilderAfterChain(): QueryBuilder
    {
        return User::query()
            ->whereNull('name')
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
        return User::query()
            ->whereNull('name')
            ->orderBy(\Illuminate\Support\Facades\DB::raw('name'))
            ->toBase();
    }

    public function testLatestToBaseWithQueryExpression(): ?QueryBuilder
    {
        return User::query()
            ->whereNull('name')
            ->latest(\Illuminate\Support\Facades\DB::raw('created_at'))
            ->toBase();
    }

    public function testOldestToBaseWithQueryExpression(): ?QueryBuilder
    {
        return User::query()
            ->whereNull('name')
            ->oldest(\Illuminate\Support\Facades\DB::raw('created_at'))
            ->toBase();
    }

    public function testPluckToBaseWithQueryExpression(): ?Collection
    {
        return User::query()
            ->whereNull('name')
            ->pluck(\Illuminate\Support\Facades\DB::raw('created_at'))
            ->toBase();
    }

    public function testIncrementWithQueryExpression(): int
    {
        return User::query()->increment(\Illuminate\Support\Facades\DB::raw('counter'));
    }

    public function testDecrementWithQueryExpression(): int
    {
        return User::query()->decrement(\Illuminate\Support\Facades\DB::raw('counter'));
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

    public function testMacro(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->macro('customMacro', function () {
        });
    }

    public function testFirstOrFailWithChain(): User
    {
        return User::with('foo')
            ->where('email', 'bar')
            ->orWhere('name', 'baz')
            ->firstOrFail();
    }

    public function testFirstWithChain(): ?User
    {
        return User::with('foo')
            ->where('email', 'bar')
            ->orWhere('name', 'baz')
            ->first();
    }

    public function testFirstWhere(): ?User
    {
        return User::query()->firstWhere(['email' => 'foo@bar.com']);
    }

    public function testOrWhereWithQueryExpression(): ?User
    {
        return User::with('foo')
            ->orWhere(\Illuminate\Support\Facades\DB::raw('name'), 'like', '%john%')
            ->first();
    }

    public function testWhereWithQueryExpression(): ?User
    {
        return User::with('foo')
            ->where(\Illuminate\Support\Facades\DB::raw('name'), 'like', '%john%')
            ->first();
    }

    public function testFirstWhereWithQueryExpression(): ?User
    {
        return User::with('foo')
            ->firstWhere(\Illuminate\Support\Facades\DB::raw('name'), 'like', '%john%');
    }

    public function testValueWithQueryExpression(): ?string
    {
        return User::with('foo')
            ->value(\Illuminate\Support\Facades\DB::raw('name'));
    }
}
