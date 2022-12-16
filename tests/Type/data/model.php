<?php

namespace Model;

use App\Post;
use App\PostBuilder;
use App\Thread;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use function PHPStan\Testing\assertType;

function testFind()
{
    assertType('App\User|null', User::find(1));
}

function testFindOnGenericModel(Model $model)
{
    assertType('Illuminate\Database\Eloquent\Model|null', $model::find(1));
}

/**
 * @param  class-string<Model>  $modelClass
 */
function testFindOnModelClassString(string $modelClass)
{
    assertType('Illuminate\Database\Eloquent\Model|null', $modelClass::find(1));
}

function testFindCanReturnCollection()
{
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::find([1, 2, 3]));
}

function testFindMany()
{
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::findMany([1, 2, 3]));
}

function testFindOrFail()
{
    assertType('App\User', User::findOrFail(1));
}

function testFindOrFailCanReturnCollection()
{
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::findOrFail([1, 2, 3]));
}

function testChainingCollectionMethodsOnModel()
{
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::findOrFail([1, 2, 3])->makeHidden('foo'));
}

function testCollectionMethodWillReturnUser()
{
    assertType('App\User|null', User::findOrFail([1, 2, 3])->makeHidden('foo')->first());
}

function testFindWithCastingToArray(FormRequest $request)
{
    /** @var array<string, string> $requestData */
    $requestData = $request->validated();

    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::find((array) $requestData['user_ids']));
}

function testFindWithCastingToInt()
{
    assertType('App\User|null', User::find((int) '1'));
}

function testMakeOnInstance(User $user)
{
    assertType('App\User', $user->make([]));
}

function testGetQueryReturnsQueryBuilder()
{
    assertType('Illuminate\Database\Query\Builder', User::getQuery());
}

function testToBaseReturnsQueryBuilder()
{
    assertType('Illuminate\Database\Query\Builder', User::toBase());
}

function testAll()
{
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::all());
}

function testJoin()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::join('tickets.tickets', 'tickets.tickets.id', '=', 'tickets.sale_ticket.ticket_id'));
}

function testWhere()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\Thread>', (new Thread)->where(['name' => 'bar']));
}

function testStaticWhere()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\Thread>', Thread::where(['name' => 'bar']));
}

function testDynamicWhere()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\Thread>', (new Thread)->whereName(['bar']));
}

function testStaticDynamicWhere()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\Thread>', Thread::whereName(['bar']));
}

function testWhereIn()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\Thread>', (new Thread)->whereIn('id', [1, 2, 3]));
}

function testIncrement(User $user)
{
    assertType('int', $user->increment('counter'));
}

function testDecrement(User $user)
{
    assertType('int', $user->decrement('counter'));
}

function testFirst()
{
    assertType('App\User|null', User::first());
}

function testMake()
{
    assertType('App\User', User::make([]));
}

function testCreate()
{
    assertType('App\User', User::create([]));
}

function testForceCreate()
{
    assertType('App\User', User::forceCreate([]));
}

function testFindOrNew()
{
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::findOrNew([]));
}

function testFirstOrNew()
{
    assertType('App\User', User::firstOrNew([]));
}

function testUpdateOrCreate()
{
    assertType('App\User', User::updateOrCreate([]));
}

function testFirstOrCreate()
{
    assertType('App\User', User::firstOrCreate([]));
}

function testScope()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\Thread>', Thread::valid());
}

function testScopeWithOrWhereHigherOrderBuilderProxyProperty()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\Thread>', Thread::valid()->orWhere->valid());
}

function testWithAcceptsArrayOfClosures()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::with(['accounts' => function ($relation) {
        return $relation->where('active', true);
    }]));
}

function testWithGlobalScope()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', (new User)->withGlobalScope('test', function () {
    }));
}

function testWithoutGlobalScope()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', (new User)->withoutGlobalScope('test'));
}

function testSoftDeletesOnlyTrashed()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::onlyTrashed());
}

function testSoftDeletesWithTrashed()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::withTrashed());
}

function testSoftDeletesWithTrashedWithArgument()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::withTrashed(false));
}

function testFindOrFailWithSoftDeletesTrait()
{
    assertType('App\User', User::onlyTrashed()->findOrFail(5));
}

function testRestore(User $user)
{
    assertType('bool', $user->restore());
}

function testFirstWhere()
{
    assertType('App\User|null', User::firstWhere(['email' => 'foo@bar.com']));
}

function testWithOnModelVariable(User $user)
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $user->with('accounts'));
}

function testMultipleWithOnModelVariable(User $user)
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $user->with('accounts')->with('group'));
}

function testLockForUpdate()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::lockForUpdate());
}

function testSharedLock()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::sharedLock());
}

function testNewQuery()
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::query());
}

function testMethodReturningCollectionOfAnotherModel()
{
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', Thread::methodReturningCollectionOfAnotherModel());
}

function testMethodReturningUnionWithCollection()
{
    assertType('App\Thread|Illuminate\Database\Eloquent\Collection<int, App\Thread>', Thread::methodReturningUnionWithCollection());
}

function testMethodReturningUnionWithCollectionOfAnotherModel()
{
    assertType('App\User|Illuminate\Database\Eloquent\Collection<int, App\User>', Thread::methodReturningUnionWithCollectionOfAnotherModel());
}

function testMin(User $user)
{
    assertType('mixed', $user->min('id'));
}

function testSole()
{
    assertType('App\User', User::sole());
}

function testRelationMethods(): void
{
    User::has('accounts', '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::has('users', '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::doesntHave('accounts', 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::doesntHave('users', 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::where(function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
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

    User::whereHas('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::whereHas('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::orWhereHas('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::orWhereHas('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::hasMorph('accounts', [], '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::hasMorph('users', [], '=', 1, 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::doesntHaveMorph('accounts', [], 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::doesntHaveMorph('users', [], 'and', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::whereHasMorph('accounts', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::whereHasMorph('users', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::orWhereHasMorph('accounts', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::orWhereHasMorph('users', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::whereDoesntHaveMorph('accounts', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::whereDoesntHaveMorph('users', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::orWhereDoesntHaveMorph('accounts', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::orWhereDoesntHaveMorph('users', [], function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::whereDoesntHave('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::whereDoesntHave('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::orWhereDoesntHave('accounts', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    });

    Post::orWhereDoesntHave('users', function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder', $query);
        //assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    User::firstWhere(function (Builder $query) {
        assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
    });

    Post::firstWhere(function (PostBuilder $query) {
        assertType('App\PostBuilder<App\Post>', $query);
    });
}
