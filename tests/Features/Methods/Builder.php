<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use App\Post;
use App\PostBuilder;
use App\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use function PHPStan\Testing\assertType;

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

    public function testQueryBuilderChainStartedWithGetQueryReturnsObject(): ?object
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

    public function testOrderByToBaseWithEloquentExpression(): ?QueryBuilder
    {
        return User::query()
            ->whereNull('name')
            ->orderBy(User::whereNotNull('name'))
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

    /** @phpstan-return Collection<array-key, mixed> */
    public function testPluckToBaseWithQueryExpression(): Collection
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

    /** @param EloquentBuilder<User> $query */
    public function testMacro(EloquentBuilder $query): void
    {
        $query->macro('customMacro', function () {
        });
    }

    /** @param EloquentBuilder<User> $query */
    public function testGlobalMacro(\Illuminate\Database\Eloquent\Builder $query): string
    {
        return $query->globalCustomMacro('foo');
    }

    public function testFirstOrFailWithChain(): User
    {
        return User::with('accounts')
            ->where('email', 'bar')
            ->orWhere('name', 'baz')
            ->firstOrFail();
    }

    public function testFirstWithChain(): ?User
    {
        return User::with('accounts')
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
        return User::with('accounts')
            ->orWhere(\Illuminate\Support\Facades\DB::raw('name'), 'like', '%john%')
            ->first();
    }

    public function testWhereWithQueryExpression(): ?User
    {
        return User::with('accounts')
            ->where(\Illuminate\Support\Facades\DB::raw('name'), 'like', '%john%')
            ->first();
    }

    public function testFirstWhereWithQueryExpression(): ?User
    {
        return User::with('accounts')
            ->firstWhere(\Illuminate\Support\Facades\DB::raw('name'), 'like', '%john%');
    }

    /** @phpstan-return mixed */
    public function testValueWithQueryExpression()
    {
        return User::with('accounts')
            ->value(\Illuminate\Support\Facades\DB::raw('name'));
    }

    /** @phpstan-return int */
    public function testRestore()
    {
        return User::query()->restore();
    }

    public function testJoinSubAllowsEloquentBuilder(): void
    {
        User::query()->joinSub(
            Post::query()->whereIn('id', [1, 2, 3]),
            'users',
            'users.id',
            'posts.id'
        );
    }

    public function testRelationMethods(): void
    {
        User::query()->has('accounts', '=', 1, 'and', function (EloquentBuilder $query) {
            assertType('Illuminate\Database\Eloquent\Builder', $query);
            //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
        });

        Post::query()->has('users', '=', 1, 'and', function (EloquentBuilder $query) {
            assertType('Illuminate\Database\Eloquent\Builder', $query);
            //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
        });

        User::query()->doesntHave('accounts', 'and', function (EloquentBuilder $query) {
            assertType('Illuminate\Database\Eloquent\Builder', $query);
            //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
        });

        Post::query()->doesntHave('users', 'and', function (EloquentBuilder $query) {
            assertType('Illuminate\Database\Eloquent\Builder', $query);
            //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
        });

        User::query()->whereHas('accounts', function (EloquentBuilder $query) {
            assertType('Illuminate\Database\Eloquent\Builder', $query);
            //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
        });

        Post::query()->whereHas('users', function (EloquentBuilder $query) {
            assertType('Illuminate\Database\Eloquent\Builder', $query);
            //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
        });

        User::query()->orWhereHas('accounts', function (EloquentBuilder $query) {
            assertType('Illuminate\Database\Eloquent\Builder', $query);
            //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
        });

        Post::query()->orWhereHas('users', function (EloquentBuilder $query) {
            assertType('Illuminate\Database\Eloquent\Builder', $query);
            //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
        });

        User::query()->hasMorph('accounts', [], '=', 1, 'and', function (EloquentBuilder $query) {
            assertType('Illuminate\Database\Eloquent\Builder', $query);
            //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
        });

        Post::query()->hasMorph('users', [], '=', 1, 'and', function (EloquentBuilder $query) {
            assertType('Illuminate\Database\Eloquent\Builder', $query);
            //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
        });

        User::query()->doesntHaveMorph('accounts', [], 'and', function (EloquentBuilder $query) {
            assertType('Illuminate\Database\Eloquent\Builder', $query);
            //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
        });

        Post::query()->doesntHaveMorph('users', [], 'and', function (EloquentBuilder $query) {
            assertType('Illuminate\Database\Eloquent\Builder', $query);
            //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
        });

        User::query()->whereHasMorph('accounts', [], function (EloquentBuilder $query) {
            assertType('Illuminate\Database\Eloquent\Builder', $query);
            //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
        });

        Post::query()->whereHasMorph('users', [], function (EloquentBuilder $query) {
            assertType('Illuminate\Database\Eloquent\Builder', $query);
            //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
        });

        User::query()->orWhereHasMorph('accounts', [], function (EloquentBuilder $query) {
            assertType('Illuminate\Database\Eloquent\Builder', $query);
            //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
        });

        Post::query()->orWhereHasMorph('users', [], function (EloquentBuilder $query) {
            assertType('Illuminate\Database\Eloquent\Builder', $query);
            //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
        });

        User::query()->whereDoesntHaveMorph('accounts', [], function (EloquentBuilder $query) {
            assertType('Illuminate\Database\Eloquent\Builder', $query);
            //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
        });

        Post::query()->whereDoesntHaveMorph('users', [], function (EloquentBuilder $query) {
            assertType('Illuminate\Database\Eloquent\Builder', $query);
            //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
        });

        User::query()->orWhereDoesntHaveMorph('accounts', [], function (EloquentBuilder $query) {
            assertType('Illuminate\Database\Eloquent\Builder', $query);
            //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
        });

        Post::query()->orWhereDoesntHaveMorph('users', [], function (EloquentBuilder $query) {
            assertType('Illuminate\Database\Eloquent\Builder', $query);
            //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
        });

        User::query()->whereDoesntHave('accounts', function (EloquentBuilder $query) {
            assertType('Illuminate\Database\Eloquent\Builder', $query);
            //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
        });

        Post::query()->whereDoesntHave('users', function (EloquentBuilder $query) {
            assertType('Illuminate\Database\Eloquent\Builder', $query);
            //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
        });

        User::query()->orWhereDoesntHave('accounts', function (EloquentBuilder $query) {
            assertType('Illuminate\Database\Eloquent\Builder', $query);
            //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
        });

        Post::query()->orWhereDoesntHave('users', function (EloquentBuilder $query) {
            assertType('Illuminate\Database\Eloquent\Builder', $query);
            //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
        });

        User::query()->firstWhere(function (EloquentBuilder $query) {
            assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
        });

        Post::query()->firstWhere(function (PostBuilder $query) {
            assertType('App\PostBuilder<App\Post>', $query);
        });
    }

    /**
     * @template TModelClass of \Illuminate\Database\Eloquent\Model
     *
     * @param  EloquentBuilder<TModelClass>  $query
     */
    public function testQueryBuilderOnEloquentBuilderWithBaseModel(EloquentBuilder $query): void
    {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $query->select());
    }

    /**
     * @phpstan-return LengthAwarePaginator<User>
     */
    public function testPaginate()
    {
        return User::query()->paginate();
    }

    /**
     * @phpstan-return array<User>
     */
    public function testPaginateItems()
    {
        return User::query()->paginate()->items();
    }
}
