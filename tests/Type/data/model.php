<?php

namespace Model;

use App\Account;
use App\Post;
use App\PostBuilder;
use App\Thread;
use App\Team;
use App\Address;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;

use function PHPStan\Testing\assertType;

class Foo
{
    public function __construct(private User $user)
    {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $this->user::query());
    }
}

class Bar extends Model
{
    use HasBar;

    public function test(): void
    {
        assertType('Illuminate\Database\Eloquent\Builder<static(Model\Bar)>', self::query());
        assertType('Illuminate\Database\Eloquent\Builder<static(Model\Bar)>', static::query());

        assertType('static(Model\Bar)|null', self::query()->first());
        assertType('static(Model\Bar)|null', static::query()->first());
    }
}

trait HasBar
{
    public static function decodeHashId(string $hash_id): array
    {
        return [];
    }

    public static function findByHashId(string $id): ?self
    {
        return self::find(static::decodeHashId($id))->first();
    }
}

/**
 * @param  class-string<Model>  $modelClass
 * @param  class-string<User>|class-string<Post>  $userOrPostClass
 * @param  class-string<User>|class-string<Account>  $userOrAccountClass
 * @param  class-string<User>|class-string<Team>  $userOrTeamClass
 */
function test(
    Model $model,
    string $modelClass,
    string $userOrPostClass,
    string $userOrAccountClass,
    string $userOrTeamClass,
    FormRequest $request,
    User $user,
): void {
    /** @var array<string, string> $requestData */
    $requestData = $request->validated();

    assertType('App\User|null', User::find(1));
    assertType('Model\Bar|null', Bar::findByHashId(1));
    assertType('Illuminate\Database\Eloquent\Model|null', $model::find(1));
    assertType('Illuminate\Database\Eloquent\Model|null', $modelClass::find(1));
    assertType('App\Post|App\User|null', $userOrPostClass::find(1));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\Post>|Illuminate\Database\Eloquent\Collection<int, App\User>', $userOrPostClass::find([1, 2, 3]));
    assertType('App\AccountCollection<int, App\Account>|Illuminate\Database\Eloquent\Collection<int, App\User>', $userOrAccountClass::find([1, 2, 3]));
    assertType('App\AccountCollection<int, App\Account>|Illuminate\Database\Eloquent\Collection<int, App\User>', $userOrAccountClass::all());

    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::find([1, 2, 3]));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::findMany([1, 2, 3]));
    assertType('App\User', User::findOrFail(1));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::findOrFail([1, 2, 3]));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::findOrFail([1, 2, 3])->makeHidden('foo'));
    assertType('App\User|null', User::findOrFail([1, 2, 3])->makeHidden('foo')->first());

    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::find((array) $requestData['user_ids']));
    assertType('App\User|null', User::find((int) '1'));
    assertType('App\User', $user->make([]));
    assertType('Illuminate\Database\Query\Builder', User::getQuery());
    assertType('Illuminate\Database\Query\Builder', User::toBase());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::all());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::join('tickets.tickets', 'tickets.tickets.id', '=', 'tickets.sale_ticket.ticket_id'));

    assertType('Illuminate\Database\Eloquent\Builder<App\Thread>', (new Thread)->where(['name' => 'bar']));
    assertType('Illuminate\Database\Eloquent\Builder<App\Thread>', Thread::where(['name' => 'bar']));
    assertType('Illuminate\Database\Eloquent\Builder<App\Thread>', (new Thread)->whereName(['bar']));
    assertType('Illuminate\Database\Eloquent\Builder<App\Thread>', Thread::whereName(['bar']));
    assertType('Illuminate\Database\Eloquent\Builder<App\Thread>', (new Thread)->whereIn('id', [1, 2, 3]));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', (new User)->withWhereHas('accounts', function ($query) {
        return $query->where('active', true);
    }));

    assertType('int', $user->increment('counter'));
    assertType('int', $user->decrement('counter'));

    assertType('App\User|null', User::first());
    assertType('App\User', User::make([]));
    assertType('App\User', User::create([]));
    assertType('App\User', User::forceCreate([]));

    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::findOrNew([]));
    assertType('App\User', User::firstOrNew([]));
    assertType('App\User', User::updateOrCreate([]));
    assertType('App\User', User::firstOrCreate([]));

    assertType('Illuminate\Database\Eloquent\Builder<App\Thread>', Thread::valid());
    assertType('Illuminate\Database\Eloquent\Builder<App\Thread>', Thread::valid()->orWhere->valid());
    assertType('Illuminate\Database\Eloquent\Builder<App\Thread>', Thread::valid()->orWhereNot->valid());
    assertType('Illuminate\Database\Eloquent\Builder<App\Thread>', Thread::valid()->whereNot->valid());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::with(['accounts' => function ($relation) {
        return $relation->where('active', true);
    }]));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', (new User)->withGlobalScope('test', function () {
    }));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', (new User)->withoutGlobalScope('test'));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::onlyTrashed());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::withTrashed());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::withTrashed(false));
    assertType('App\User', User::onlyTrashed()->findOrFail(5));
    assertType('bool', $user->restore());
    assertType('App\User', User::restoreOrCreate(['id' => 1]));
    assertType('App\User|null', User::firstWhere(['email' => 'foo@bar.com']));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $user->with('accounts'));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $user->with('accounts')->with('group'));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $user->with(['accounts' => function (Relation $relation) {
        //assertType('Illuminate\Database\Eloquent\Relations\Relation<Illuminate\Database\Eloquent\Model>', $relation->orderBy('id'));
    }]));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::lockForUpdate());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::sharedLock());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::query());

    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', Thread::methodReturningCollectionOfAnotherModel());
    assertType('App\Thread|Illuminate\Database\Eloquent\Collection<int, App\Thread>', Thread::methodReturningUnionWithCollection());
    assertType('App\User|Illuminate\Database\Eloquent\Collection<int, App\User>', Thread::methodReturningUnionWithCollectionOfAnotherModel());
    assertType('mixed', $user->min('id'));
    assertType('App\User', User::sole());

    /** @var class-string<User> $className */
    $className = User::class;

    assertType('App\User', new $className());
    assertType('App\User', $className::create());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', (new $className())->newQuery());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $className::query());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $className::query()->active());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $className::query()->active()->get());

    User::where(function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    Team::where(function ($query) {
        assertType('App\ChildTeamBuilder', $query);
    });

    Post::where(function (PostBuilder $query) {
        assertType('App\PostBuilder<App\Post>', $query);
    });

    User::orWhere(function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    Post::orWhere(function (PostBuilder $query) {
        assertType('App\PostBuilder<App\Post>', $query);
    });

    User::has('accounts', callback: function ($query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    User::has('accounts', '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    User::has('accounts.posts', '=', 1, 'and', function (Builder $query) {
        assertType('App\PostBuilder<App\Post>', $query);
    });

    Post::has('users', '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::doesntHave('accounts', 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    User::whereHas('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::withWhereHas('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::orWhereHas('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    User::whereDoesntHave('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    User::orWhereDoesntHave('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::whereRelation('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::orWhereRelation('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    /** @var 'accounts'|'address' $relation */
    $relation = 'address';
    User::whereHas($relation, function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account|App\Address>', $query);
    });

    // currently a bug in PHPStan: https://github.com/phpstan/phpstan/issues/11742
    $user::has($user->accounts(), callback: function ($query) {
        // assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    $userOrTeamClass::has('address', function ($query) {
        // assertType('Illuminate\Database\Eloquent\Builder<App\Address>', $query);
    });

    $userOrTeamClass::has('members', function ($query) {
        // assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    $userOrTeamClass::has('transactions', function ($query) {
        // assertType('Illuminate\Database\Eloquent\Builder<App\Transaction>', $query);
    });

    Address::hasMorph('addressable', [User::class, Team::class], callable: function ($query) {
        assertType('App\ChildTeamBuilder|Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    Address::hasMorph('addressable', User::class, '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    Address::hasMorph('addressable', '*', callback: function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $query);
    });

    Address::doesntHaveMorph('addressable', [User::class], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    Address::whereHasMorph('addressable', [User::class], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    Address::orWhereHasMorph('addressable', [User::class], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    Address::whereDoesntHaveMorph('addressable', [User::class], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    Address::orWhereDoesntHaveMorph('addressable', [User::class], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    Address::whereMorphRelation('addressable', [User::class], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    Address::orWhereMorphRelation('addressable', [User::class], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::firstWhere(function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    Post::firstWhere(function (PostBuilder $query) {
        assertType('App\PostBuilder<App\Post>', $query);
    });
}
