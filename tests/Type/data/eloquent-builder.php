<?php

declare(strict_types=1);

namespace EloquentBuilder;

use App\Post;
use App\PostBuilder;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use function PHPStan\Testing\assertType;

function foo(): void
{
    User::query()->has('accounts', '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::query()->has('users', '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::query()->doesntHave('accounts', 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::query()->doesntHave('users', 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::query()->whereHas('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::query()->whereHas('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::query()->orWhereHas('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::query()->orWhereHas('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::query()->hasMorph('accounts', [], '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::query()->hasMorph('users', [], '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::query()->doesntHaveMorph('accounts', [], 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::query()->doesntHaveMorph('users', [], 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::query()->whereHasMorph('accounts', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::query()->whereHasMorph('users', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::query()->orWhereHasMorph('accounts', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::query()->orWhereHasMorph('users', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::query()->whereDoesntHaveMorph('accounts', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::query()->whereDoesntHaveMorph('users', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::query()->orWhereDoesntHaveMorph('accounts', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::query()->orWhereDoesntHaveMorph('users', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::query()->whereDoesntHave('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::query()->whereDoesntHave('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::query()->orWhereDoesntHave('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::query()->orWhereDoesntHave('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::query()->firstWhere(function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    Post::query()->firstWhere(function (PostBuilder $query) {
        assertType('App\PostBuilder<App\Post>', $query);
    });

    Post::query()->where(static function (PostBuilder $query) {
        assertType('App\PostBuilder<App\Post>', $query
            ->orWhere('bar', 'LIKE', '%foo%')
            ->orWhereRelation('users', 'name', 'LIKE', '%foo%'));
    });

    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::where([
        ['active', true],
        ['id', '>=', 5],
        ['id', '<=', 10],
    ])->get());

    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::where('id', 1)->get());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', (new User)->where('id', 1)->get());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::where('id', 1)
        ->whereNotNull('name')
        ->where('email', 'bar')
        ->whereFoo(['bar'])
        ->get());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', (new User)->whereNotNull('name')
        ->where('email', 'bar')
        ->whereFoo(['bar'])
        ->get());
    assertType('Illuminate\Support\Collection<string, string>', User::whereIn('id', [1, 2, 3])->get()->mapWithKeys(function (User $user): array {
        return [$user->name => $user->email];
    }));

    assertType('mixed', (new User)->where('email', 1)->max('email'));
    assertType('bool', (new User)->where('email', 1)->exists());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::with('accounts')->whereNull('name'));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::with('accounts')
        ->where('email', 'bar')
        ->orWhere('name', 'baz'));

    assertType('App\User|null', User::with(['accounts'])->find(1));
    assertType('App\User', User::with(['accounts'])->findOrFail(1));
    assertType('App\User', User::with(['accounts'])->findOrNew(1));
    assertType('Illuminate\Database\Eloquent\Model|null', (new CustomBuilder(User::query()->getQuery()))->with('email')->find(1));

    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::with(['accounts'])->find([1, 2, 3]));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::with(['accounts'])->findOrNew([1, 2, 3]));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::hydrate([]));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::fromQuery('SELECT * FROM users'));
}

/**
 * @param  Builder<User>  $builder
 */
function testCallingQueryBuilderMethodOnEloquentBuilderReturnsEloquentBuilder(Builder $builder): void
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $builder->whereNotNull('test'));
}

function doFoo(User $user, Post $post): void
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $user->newQuery());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $user->newModelQuery());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $user->newQueryWithoutRelationships());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $user->newQueryWithoutScopes());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $user->newQueryWithoutScope('foo'));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $user->newQueryForRestoration([1]));

    assertType('App\PostBuilder<App\Post>', $post->newQuery());
    assertType('App\PostBuilder<App\Post>', $post->newModelQuery());
    assertType('App\PostBuilder<App\Post>', $post->newQueryWithoutRelationships());
    assertType('App\PostBuilder<App\Post>', $post->newQueryWithoutScopes());
    assertType('App\PostBuilder<App\Post>', $post->newQueryWithoutScope('foo'));
    assertType('App\PostBuilder<App\Post>', $post->newQueryForRestoration([1]));

    assertType('Illuminate\Support\LazyCollection<int, App\User>', User::query()->lazy());
    assertType('Illuminate\Support\LazyCollection<int, App\User>', User::query()->lazyById());
    assertType('Illuminate\Support\LazyCollection<int, App\User>', User::query()->lazyByIdDesc());
    assertType('Illuminate\Support\LazyCollection<int, App\Post>', $post->newQuery()->lazy());
    assertType('Illuminate\Support\LazyCollection<int, App\Post>', $post->newQuery()->lazyById());
    assertType('Illuminate\Support\LazyCollection<int, App\Post>', $post->newQuery()->lazyByIdDesc());
};

function testGroupBy()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::query()->groupBy('foo', 'bar'));
}

function testDynamicWhereAsString()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::query()->whereEmail('bar'));
}

function testDynamicWhereMultiple()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::query()->whereIdAndEmail(1, 'foo@example.com'));
}

function testDynamicWhereAsInt()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::query()->whereEmail(1));
}

function testToBaseReturnsQueryBuilderAfterChain()
{
    assertType('Illuminate\Database\Query\Builder', User::query()
        ->whereNull('name')
        ->orderBy('email')
        ->toBase()
    );
}

function testQueryBuilderChainStartedWithGetQueryReturnsObject()
{
    assertType('object|null', User::getQuery()
        ->select('some_model.created')
        ->where('some_model.some_column', '=', true)
        ->orderBy('some_model.created', 'desc')
        ->first()
    );
}

function testWhereNotBetweenInt()
{
    assertType('Illuminate\Database\Query\Builder', User::query()
        ->whereNotBetween('a', [1, 5])
        ->orWhereNotBetween('a', [1, 5])
        ->toBase()
    );
}

function testWithTrashedOnBuilderWithModel()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::query()->withTrashed());
}

function testOrderByToBaseWithQueryExpression()
{
    assertType('Illuminate\Database\Query\Builder', User::query()
        ->whereNull('name')
        ->orderBy(\Illuminate\Support\Facades\DB::raw('name'))
        ->toBase()
    );
}

function testOrderByToBaseWithEloquentExpression()
{
    assertType('Illuminate\Database\Query\Builder', User::query()
        ->whereNull('name')
        ->orderBy(User::whereNotNull('name'))
        ->toBase()
    );
}

function testLatestToBaseWithQueryExpression()
{
    assertType('Illuminate\Database\Query\Builder', User::query()
        ->whereNull('name')
        ->latest(\Illuminate\Support\Facades\DB::raw('created_at'))
        ->toBase()
    );
}

function testOldestToBaseWithQueryExpression()
{
    assertType('Illuminate\Database\Query\Builder', User::query()
        ->whereNull('name')
        ->oldest(\Illuminate\Support\Facades\DB::raw('created_at'))
        ->toBase()
    );
}

function testPluckToBaseWithQueryExpression()
{
    assertType('Illuminate\Support\Collection<(int|string), mixed>', User::query()
        ->whereNull('name')
        ->pluck(\Illuminate\Support\Facades\DB::raw('created_at'))
        ->toBase()
    );
}

function testIncrementWithQueryExpression()
{
    assertType('int', User::query()->increment(\Illuminate\Support\Facades\DB::raw('counter')));
}

function testDecrementWithQueryExpression()
{
    assertType('int', User::query()->decrement(\Illuminate\Support\Facades\DB::raw('counter')));
}

/** @param EloquentBuilder<User> $query */
function testMacro(EloquentBuilder $query): void
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query->macro('customMacro', function () {
    }));
}

/** @param EloquentBuilder<User> $query */
function testGlobalMacro(\Illuminate\Database\Eloquent\Builder $query)
{
    assertType('string', $query->globalCustomMacro('foo'));
}

function testFirstOrFailWithChain()
{
    assertType('App\User', User::with('accounts')
        ->where('email', 'bar')
        ->orWhere('name', 'baz')
        ->firstOrFail()
    );
}

function testFirstWithChain()
{
    assertType('App\User|null', User::with('accounts')
        ->where('email', 'bar')
        ->orWhere('name', 'baz')
        ->first()
    );
}

function testFirstWhere()
{
    assertType('App\User|null', User::query()->firstWhere(['email' => 'foo@bar.com']));
}

function testOrWhereWithQueryExpression()
{
    assertType('App\User|null', User::with('accounts')
        ->orWhere(\Illuminate\Support\Facades\DB::raw('name'), 'like', '%john%')
        ->first()
    );
}

function testWhereWithQueryExpression()
{
    assertType('App\User|null', User::with('accounts')
        ->where(\Illuminate\Support\Facades\DB::raw('name'), 'like', '%john%')
        ->first()
    );
}

function testFirstWhereWithQueryExpression()
{
    assertType('App\User|null', User::with('accounts')
        ->firstWhere(\Illuminate\Support\Facades\DB::raw('name'), 'like', '%john%')
    );
}

/** @phpstan-return mixed */
function testValueWithQueryExpression()
{
    assertType('mixed', User::with('accounts')
        ->value(\Illuminate\Support\Facades\DB::raw('name'))
    );
}

function testRestore()
{
    assertType('int', User::query()->restore());
}

function testJoinSubAllowsEloquentBuilder()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::query()->joinSub(
        Post::query()->whereIn('id', [1, 2, 3]),
        'users',
        'users.id',
        'posts.id'
    ));
}

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 *
 * @param  EloquentBuilder<TModelClass>  $query
 */
function testQueryBuilderOnEloquentBuilderWithBaseModel(EloquentBuilder $query): void
{
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $query->select());
}

function testPaginate()
{
    assertType('Illuminate\Pagination\LengthAwarePaginator<App\User>', User::query()->paginate());
}

/**
 * @phpstan-return array<User>
 */
function testPaginateItems()
{
    assertType('array<App\User>', User::query()->paginate()->items());
}

class Foo extends Model
{
    /** @phpstan-use FooTrait<Foo> */
    use FooTrait;
}

/** @template TModelClass of Model */
trait FooTrait
{
    /** @return Builder<TModelClass> */
    public function doFoo(): Builder
    {
        return $this->newQuery();
    }
}

/**
 * @property string $email
 */
class TestModel extends Model
{
    public function testCallingGetInsideModel()
    {
        assertType('Illuminate\Database\Eloquent\Collection<int, EloquentBuilder\TestModel>', $this->where('email', 1)->get());
    }

    public function testStaticQuery()
    {
        assertType('Illuminate\Database\Eloquent\Builder<EloquentBuilder\TestModel>', static::query()->where('email', 'bar'));
    }

    public function testQuery(): Builder
    {
        assertType('Illuminate\Database\Eloquent\Builder<EloquentBuilder\TestModel>', $this->where('email', 'bar'));
    }
}

/** @extends Builder<Model> */
class CustomBuilder extends Builder
{
}
