<?php

declare(strict_types=1);

namespace EloquentBuilder;

use App\Post;
use App\PostBuilder;
use App\Team;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

use function PHPStan\Testing\assertType;

interface OnlyUsers
{ }

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @param Builder<User> $userBuilder
 * @param Builder<User|Team> $userOrTeamBuilder
 * @param Builder<TModel> $templateBuilder
 */
function test(
    User $user,
    Post $post,
    Builder $userBuilder,
    OnlyUsers&User $userAndAuth,
    Builder $userOrTeamBuilder,
    Builder $templateBuilder,
): void {
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

    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $userBuilder->whereNotNull('test'));

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

    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $userAndAuth->newQuery());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $userAndAuth->newModelQuery());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $userAndAuth->newQueryWithoutRelationships());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $userAndAuth->newQueryWithoutScopes());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $userAndAuth->newQueryWithoutScope('foo'));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $userAndAuth->newQueryForRestoration([1]));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $userAndAuth::query());

    assertType('Illuminate\Support\LazyCollection<int, App\User>', User::query()->lazy());
    assertType('Illuminate\Support\LazyCollection<int, App\User>', User::query()->lazyById());
    assertType('Illuminate\Support\LazyCollection<int, App\User>', User::query()->lazyByIdDesc());
    assertType('Illuminate\Support\LazyCollection<int, App\User>', User::query()->cursor());
    assertType('Illuminate\Support\LazyCollection<int, App\Post>', $post->newQuery()->lazy());
    assertType('Illuminate\Support\LazyCollection<int, App\Post>', $post->newQuery()->lazyById());
    assertType('Illuminate\Support\LazyCollection<int, App\Post>', $post->newQuery()->lazyByIdDesc());

    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::query()->groupBy('foo', 'bar'));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::query()->whereEmail('bar'));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::query()->whereIdAndEmail(1, 'foo@example.com'));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::query()->whereEmail(1));
    assertType('Illuminate\Database\Query\Builder', User::query()
        ->whereNull('name')
        ->orderBy('email')
        ->toBase()
    );
    assertType('object|null', User::getQuery()
        ->select('some_model.created')
        ->where('some_model.some_column', '=', true)
        ->orderBy('some_model.created', 'desc')
        ->first()
    );
    assertType('Illuminate\Database\Query\Builder', User::query()
        ->whereNotBetween('a', [1, 5])
        ->orWhereNotBetween('a', [1, 5])
        ->toBase()
    );
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::query()->withTrashed());
    assertType('Illuminate\Database\Query\Builder', User::query()
        ->whereNull('name')
        ->orderBy(\Illuminate\Support\Facades\DB::raw('name'))
        ->toBase()
    );
    assertType('Illuminate\Database\Query\Builder', User::query()
        ->whereNull('name')
        ->orderBy(User::whereNotNull('name'))
        ->toBase()
    );
    assertType('Illuminate\Database\Query\Builder', User::query()
        ->whereNull('name')
        ->latest(\Illuminate\Support\Facades\DB::raw('created_at'))
        ->toBase()
    );
    assertType('Illuminate\Database\Query\Builder', User::query()
        ->whereNull('name')
        ->oldest(\Illuminate\Support\Facades\DB::raw('created_at'))
        ->toBase()
    );
    assertType('Illuminate\Support\Collection<(int|string), mixed>', User::query()
        ->whereNull('name')
        ->pluck(\Illuminate\Support\Facades\DB::raw('created_at'))
        ->toBase()
    );
    assertType('int', User::query()->increment(\Illuminate\Support\Facades\DB::raw('counter')));
    assertType('int', User::query()->decrement(\Illuminate\Support\Facades\DB::raw('counter')));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $userBuilder->macro('customMacro', function () {
    }));
    assertType('string', $userBuilder->globalCustomMacro('foo'));
    assertType('App\User', User::with('accounts')
        ->where('email', 'bar')
        ->orWhere('name', 'baz')
        ->firstOrFail()
    );
    assertType('App\User|null', User::with('accounts')
        ->where('email', 'bar')
        ->orWhere('name', 'baz')
        ->first()
    );
    assertType('App\User|null', User::query()->firstWhere(['email' => 'foo@bar.com']));
    assertType('App\User|null', User::with('accounts')
        ->orWhere(\Illuminate\Support\Facades\DB::raw('name'), 'like', '%john%')
        ->first()
    );
    assertType('App\User|null', User::with('accounts')
        ->where(\Illuminate\Support\Facades\DB::raw('name'), 'like', '%john%')
        ->first()
    );
    assertType('App\User|null', User::with('accounts')
        ->firstWhere(\Illuminate\Support\Facades\DB::raw('name'), 'like', '%john%')
    );
    assertType('mixed', User::with('accounts')
        ->value(\Illuminate\Support\Facades\DB::raw('name'))
    );
    assertType('int', User::query()->restore());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::query()->joinSub(
        Post::query()->whereIn('id', [1, 2, 3]),
        'users',
        'users.id',
        'posts.id'
    ));

    assertType('Illuminate\Pagination\LengthAwarePaginator<App\User>', User::query()->paginate());
    assertType('array<App\User>', User::query()->paginate()->items());

    User::chunk(1000, fn ($collection) => assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $collection));

    assertType('App\Team|App\User', $userOrTeamBuilder->findOrFail(4));
    assertType('Illuminate\Database\Eloquent\Builder<App\Team|App\User>', $userOrTeamBuilder->where('id', 5));

    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $templateBuilder->select());
}

class Foo extends Model
{
    /** @use FooTrait<Foo> */
    use FooTrait;
}

/** @template TModel of Model */
trait FooTrait
{
    /** @return Builder<TModel> */
    public function doFoo(): Builder
    {
        return $this->newQuery();
    }
}

/** @property string $email */
class TestModel extends Model
{
    public function test(): void
    {
        assertType('Illuminate\Database\Eloquent\Collection<int, EloquentBuilder\TestModel>', $this->where('email', 1)->get());
        assertType('Illuminate\Database\Eloquent\Builder<static(EloquentBuilder\TestModel)>', static::query()->where('email', 'bar'));
        assertType('Illuminate\Database\Eloquent\Builder<EloquentBuilder\TestModel>', $this->where('email', 'bar'));
    }
}

/** @extends Builder<Model> */
class CustomBuilder extends Builder
{
}

/** @template TModel of User|Team */
abstract class UnionClass
{
    /** @return TModel */
    public function test(int $id): Model
    {
        assertType('TModel of App\Team|App\User (class EloquentBuilder\UnionClass, argument)', $this->getQuery()->findOrFail($id));

        return $this->getQuery()->findOrFail($id);
    }

    /** @return Builder<TModel> */
    abstract public function getQuery(): Builder;
}

/** @extends UnionClass<Team> */
class TeamClass extends UnionClass
{
    public function foo()
    {
        assertType('App\Team', $this->test(5));
    }

    /** @inheritDoc */
    public function getQuery(): Builder
    {
        return Team::query();
    }
}
