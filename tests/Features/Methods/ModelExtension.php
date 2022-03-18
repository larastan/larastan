<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use App\Post;
use App\PostBuilder;
use App\Thread;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use function PHPStan\Testing\assertType;

class ModelExtension
{
    public function testMakeOnInstance(User $user): User
    {
        return $user->make([]);
    }

    public function testGetQueryReturnsQueryBuilder(): QueryBuilder
    {
        return User::getQuery();
    }

    public function testToBaseReturnsQueryBuilder(): QueryBuilder
    {
        return User::toBase();
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testAll(): Collection
    {
        return User::all();
    }

    /** @phpstan-return Builder<User> */
    public function testJoin(): Builder
    {
        return User::join('tickets.tickets', 'tickets.tickets.id', '=', 'tickets.sale_ticket.ticket_id');
    }

    /** @phpstan-return Builder<Thread> */
    public function testWhere(): Builder
    {
        return (new Thread)->where(['name' => 'bar']);
    }

    /** @phpstan-return Builder<Thread> */
    public function testStaticWhere(): Builder
    {
        return Thread::where(['name' => 'bar']);
    }

    /** @phpstan-return Builder<Thread> */
    public function testDynamicWhere(): Builder
    {
        return (new Thread)->whereName(['bar']);
    }

    /** @phpstan-return Builder<Thread> */
    public function testStaticDynamicWhere(): Builder
    {
        return Thread::whereName(['bar']);
    }

    /** @phpstan-return Builder<Thread> */
    public function testWhereIn(): Builder
    {
        return (new Thread)->whereIn('id', [1, 2, 3]);
    }

    public function testIncrement(): int
    {
        /** @var User $user */
        $user = new User;

        return $user->increment('counter');
    }

    public function testIncrementOnSeperateLine(): void
    {
        /** @var User $user */
        $user = new User;

        $user->increment('counter');
    }

    public function testDecrement(): int
    {
        /** @var User $user */
        $user = new User;

        return $user->decrement('counter');
    }

    public function testDecrementOnSeperateLine(): void
    {
        /** @var User $user */
        $user = new User;

        $user->decrement('counter');
    }

    public function testFirst(): ?User
    {
        return User::first();
    }

    public function testMake(): User
    {
        return User::make([]);
    }

    public function testCreate(): User
    {
        return User::create([]);
    }

    public function testForceCreate(): User
    {
        return User::forceCreate([]);
    }

    public function testFindOrNew(): User
    {
        return User::findOrNew([]);
    }

    public function testFirstOrNew(): User
    {
        return User::firstOrNew([]);
    }

    public function testUpdateOrCreate(): User
    {
        return User::updateOrCreate([]);
    }

    public function testFirstOrCreate(): User
    {
        return User::firstOrCreate([]);
    }

    /** @phpstan-return Builder<Thread> */
    public function testScope(): Builder
    {
        return Thread::valid();
    }

    /** @phpstan-return Builder<Thread> */
    public function testScopeWithOrWhereHigherOrderBuilderProxyProperty(): Builder
    {
        return Thread::valid()->orWhere->valid();
    }

    /** @phpstan-return Builder<User> */
    public function testWithAcceptsArrayOfClosures(): Builder
    {
        return User::with(['accounts' => function ($relation) {
            return $relation->where('active', true);
        }]);
    }

    /** @phpstan-return Builder<User> */
    public function testWithGlobalScope(): Builder
    {
        return (new User)->withGlobalScope('test', function () {
        });
    }

    /** @phpstan-return Builder<User> */
    public function testWithoutGlobalScope(): Builder
    {
        return (new User)->withoutGlobalScope('test');
    }

    /** @phpstan-return Builder<User> */
    public function testSoftDeletesOnlyTrashed(): Builder
    {
        return User::onlyTrashed();
    }

    /** @phpstan-return Builder<User> */
    public function testSoftDeletesWithTrashed(): Builder
    {
        return User::withTrashed();
    }

    /** @phpstan-return Builder<User> */
    public function testSoftDeletesWithTrashedWithArgument(): Builder
    {
        return User::withTrashed(false);
    }

    public function testFindOrFailWithSoftDeletesTrait(): User
    {
        return User::onlyTrashed()->findOrFail(5);
    }

    public function testRestore(User $user): bool
    {
        return $user->restore();
    }

    public function testFirstWhere(): ?User
    {
        return User::firstWhere(['email' => 'foo@bar.com']);
    }

    /** @phpstan-return Builder<User> */
    public function testWithOnModelVariable(User $user): Builder
    {
        return $user->with('accounts');
    }

    /** @phpstan-return Builder<User> */
    public function testMultipleWithOnModelVariable(User $user): Builder
    {
        return $user->with('accounts')->with('group');
    }

    /** @phpstan-return Builder<User> */
    public function testLockForUpdate(): Builder
    {
        return User::lockForUpdate();
    }

    /** @phpstan-return Builder<User> */
    public function testSharedLock(): Builder
    {
        return User::sharedLock();
    }

    /** @phpstan-return Builder<User> */
    public function testNewQuery(): Builder
    {
        return User::query();
    }

    /** @phpstan-return Collection<int, User> */
    public function testMethodReturningCollectionOfAnotherModel()
    {
        return Thread::methodReturningCollectionOfAnotherModel();
    }

    /** @phpstan-return Collection<int, Thread>|Thread */
    public function testMethodReturningUnionWithCollection()
    {
        return Thread::methodReturningUnionWithCollection();
    }

    /** @phpstan-return Collection<int, User>|User */
    public function testMethodReturningUnionWithCollectionOfAnotherModel()
    {
        return Thread::methodReturningUnionWithCollectionOfAnotherModel();
    }

    /** @phpstan-return mixed */
    public function testMin(User $user)
    {
        return $user->min('id');
    }

    public function testSole(): User
    {
        return User::sole();
    }

    public function testRelationMethods(): void
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
}
