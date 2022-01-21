<?php

namespace Tests\Features\ReturnTypes;

use App\Account;
use App\AccountCollection;
use App\Group;
use App\Role;
use App\RoleCollection;
use App\Transaction;
use App\TransactionCollection;
use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use function PHPStan\Testing\assertType;

class CustomEloquentCollectionTest
{
    // Query builder...

    /**
     * @phpstan-return AccountCollection<int, Account>
     */
    public function testQueryBuilderFromQueryViaQueryReturnsCustomCollection(): AccountCollection
    {
        return Account::query()->fromQuery('select * from accounts');
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testQueryBuilderFromQueryViaQueryReturnsEloquentCollection(): Collection
    {
        return User::query()->fromQuery('select * from accounts');
    }

    /**
     * @phpstan-return AccountCollection<int, Account>
     */
    public function testQueryBuilderFromQueryViaStaticCallOnModelReturnsCustomCollection(): AccountCollection
    {
        return Account::fromQuery('select * from accounts');
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testQueryBuilderFromQueryViaStaticCallOnModelReturnsEloquentCollection(): Collection
    {
        return User::fromQuery('select * from accounts');
    }

    /**
     * @phpstan-return AccountCollection<int, Account>
     */
    public function testQueryBuilderHydrateViaQueryReturnsCustomCollection(): AccountCollection
    {
        return Account::query()->hydrate([
            ['active' => 1],
            ['active' => 0],
        ]);
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testQueryBuilderHydrateViaQueryReturnsEloquentCollection(): Collection
    {
        return User::query()->hydrate([
            ['active' => 1],
            ['active' => 0],
        ]);
    }

    /**
     * @phpstan-return AccountCollection<int, Account>
     */
    public function testQueryBuilderHydrateViaStaticCallOnModelReturnsCustomCollection(): AccountCollection
    {
        return Account::hydrate([
            ['active' => 1],
            ['active' => 0],
        ]);
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testQueryBuilderHydrateViaStaticCallOnModelReturnsEloquentCollection(): Collection
    {
        return User::hydrate([
            ['active' => 1],
            ['active' => 0],
        ]);
    }

    /**
     * @phpstan-return AccountCollection<int, Account>
     */
    public function testQueryBuilderFindViaQueryReturnsCustomCollection(): AccountCollection
    {
        return Account::query()->find([1, 2]);
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testQueryBuilderFindViaQueryReturnsEloquentCollection(): Collection
    {
        return User::query()->find([1, 2]);
    }

    /**
     * @phpstan-return AccountCollection<int, Account>
     */
    public function testQueryBuilderFindViaStaticCallOnModelReturnsCustomCollection(): AccountCollection
    {
        return Account::find([1, 2]);
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testQueryBuilderFindViaStaticCallOnModelReturnsEloquentCollection(): Collection
    {
        return User::find([1, 2]);
    }

    /**
     * @phpstan-return AccountCollection<int, Account>
     */
    public function testQueryBuilderFindManyViaQueryReturnsCustomCollection(): AccountCollection
    {
        return Account::query()->findMany([1, 2]);
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testQueryBuilderFindManyViaQueryReturnsEloquentCollection(): Collection
    {
        return User::query()->findMany([1, 2]);
    }

    /**
     * @phpstan-return AccountCollection<int, Account>
     */
    public function testQueryBuilderFindManyViaStaticCallOnModelReturnsCustomCollection(): AccountCollection
    {
        return Account::findMany([1, 2]);
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testQueryBuilderFindManyViaStaticCallOnModelReturnsEloquentCollection(): Collection
    {
        return User::findMany([1, 2]);
    }

    /**
     * @phpstan-return AccountCollection<int, Account>
     */
    public function testQueryBuilderFindOrFailViaQueryReturnsCustomCollection(): AccountCollection
    {
        return Account::query()->findOrFail([1, 2]);
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testQueryBuilderFindOrFailViaQueryReturnsEloquentCollection(): Collection
    {
        return User::query()->findOrFail([1, 2]);
    }

    /**
     * @phpstan-return AccountCollection<int, Account>
     */
    public function testQueryBuilderFindOrFailViaStaticCallOnModelCallReturnsCustomCollection(): AccountCollection
    {
        return Account::findOrFail([1, 2]);
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testQueryBuilderFindOrFailViaStaticCallOnModelCallReturnsEloquentCollection(): Collection
    {
        return User::findOrFail([1, 2]);
    }

    public function testQueryBuilderFindOrFailExpectingSingleModelViaQueryDoesntReturnCollection(): Account
    {
        return Account::query()->findOrFail(1);
    }

    public function testQueryBuilderFindOrFailExpectingSingleModelViaStaticCallOnModelDoesntReturnCollection(): Account
    {
        return Account::findOrFail(1);
    }

    /**
     * @phpstan-return AccountCollection<int, Account>
     */
    public function testQueryBuilderGetViaQueryReturnsCustomCollection(): AccountCollection
    {
        return Account::query()->get();
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testQueryBuilderGetViaQueryReturnsEloquentCollection(): Collection
    {
        return User::query()->get();
    }

    /**
     * @phpstan-return AccountCollection<int, Account>
     */
    public function testQueryBuilderGetViaStaticOnModelCallReturnsCustomCollection(): AccountCollection
    {
        return Account::get();
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testQueryBuilderGetViaStaticOnModelCallReturnsEloquentCollection(): Collection
    {
        return User::get();
    }

    // Model...

    /**
     * @phpstan-return AccountCollection<int, Account>
     */
    public function testModelAllReturnsCustomCollection(): AccountCollection
    {
        return Account::all();
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testModelAllReturnsEloquentCollection(): Collection
    {
        return User::all();
    }

    // Relation...

    /**
     * @phpstan-return AccountCollection<int, Account>
     */
    public function testRelationGetReturnsCustomCollection(): AccountCollection
    {
        return (new User)->accounts()->get();
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testRelationGetReturnsEloquentCollection(): Collection
    {
        return (new User)->children()->get();
    }

    /**
     * @phpstan-return AccountCollection<int, Account>
     */
    public function testRelationGetEagerReturnsCustomCollection(): AccountCollection
    {
        return (new User)->accounts()->getEager();
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testRelationGetEagerReturnsEloquentCollection(): Collection
    {
        return (new User)->children()->getEager();
    }

    /**
     * @phpstan-return AccountCollection<int, Account>
     */
    public function testRelationCreateManyReturnsCustomCollection(): AccountCollection
    {
        return (new User)->accounts()->createMany([
            //
        ]);
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testRelationCreateManyReturnsEloquentCollection(): Collection
    {
        return (new User)->children()->createMany([
            //
        ]);
    }

    /**
     * @phpstan-return AccountCollection<int, Account>
     */
    public function testRelationQueryWithWhereReturnsCustomCollection(): AccountCollection
    {
        return (new User)->accounts()->active()->get();
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testRelationQueryWithWhereReturnsEloquentCollection(): Collection
    {
        return (new User)->children()->active()->get();
    }

    /**
     * @phpstan-return AccountCollection<int, Account>
     */
    public function testRelationAttributeReturnsCustomCollection(): AccountCollection
    {
        return (new User)->accounts;
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testRelationAttributeReturnsEloquentCollection(): Collection
    {
        return (new User)->children;
    }

    /**
     * @phpstan-return RoleCollection<int, Role>
     */
    public function testRelationBelongsToManyFindReturnsCustomCollection(): RoleCollection
    {
        return (new User)->roles()->find([1, 2]);
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testRelationBelongsToManyFindReturnsEloquentCollection(): Collection
    {
        return (new Role)->users()->find([1, 2]);
    }

    public function testRelationBelongsToManyFindExpectingSingleModelDoesntReturnACustomCollection(): ?Role
    {
        return (new User)->roles()->find(1);
    }

    /**
     * @phpstan-return RoleCollection<int, Role>
     */
    public function testRelationBelongsToManyFindOrFailReturnsCustomCollection(): RoleCollection
    {
        return (new User)->roles()->findOrFail([1, 2]);
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testRelationBelongsToManyFindOrFailReturnsEloquentCollection(): Collection
    {
        return (new Role)->users()->findOrFail([1, 2]);
    }

    public function testRelationBelongsToManyFindOrFailExpectingSingleModelDoesntReturnACustomCollection(): Role
    {
        return (new User)->roles()->findOrFail(1);
    }

    /**
     * @phpstan-return RoleCollection<int, Role>
     */
    public function testRelationBelongsToManyFindManyReturnsCustomCollection(): RoleCollection
    {
        return (new User)->roles()->findMany([1, 2]);
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testRelationBelongsToManyFindManyReturnsEloquentCollection(): Collection
    {
        return (new Role)->users()->findMany([1, 2]);
    }

    /**
     * @phpstan-return TransactionCollection<int, Transaction>
     */
    public function testRelationHasManyThroughManyFindReturnsCustomCollection(): TransactionCollection
    {
        return (new User)->transactions()->find([1, 2]);
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testRelationHasManyThroughManyFindReturnsEloquentCollection(): Collection
    {
        return (new Role)->users()->find([1, 2]);
    }

    public function testRelationHasManyThroughManyFindExpectingSingleModelDoesntReturnACustomCollection(): ?Model
    {
        return (new User)->transactions()->find(1);
    }

    /**
     * @phpstan-return TransactionCollection<int, Transaction>
     */
    public function testRelationHasManyThroughManyFindOrFailReturnsCustomCollection(): TransactionCollection
    {
        return (new User)->transactions()->findOrFail([1, 2]);
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testRelationHasManyThroughManyFindOrFailReturnsEloquentCollection(): Collection
    {
        return (new Role)->users()->findOrFail([1, 2]);
    }

    public function testRelationHasManyThroughManyFindOrFailExpectingSingleModelDoesntReturnACustomCollection(): Model
    {
        return (new User)->transactions()->findOrFail(1);
    }

    /**
     * @phpstan-return TransactionCollection<int, Transaction>
     */
    public function testRelationHasManyThroughManyFindManyReturnsCustomCollection(): TransactionCollection
    {
        return (new User)->transactions()->findMany([1, 2]);
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testRelationHasManyThroughManyFindManyReturnsEloquentCollection(): Collection
    {
        return (new Role)->users()->findMany([1, 2]);
    }

    /**
     * @phpstan-return AccountCollection<int, Account>
     */
    public function testRelationHasManyFindReturnsCustomCollection(): AccountCollection
    {
        return (new Group)->accounts()->find([1, 2]);
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testRelationHasManyFindReturnsEloquentCollection(): Collection
    {
        return (new User)->children()->find([1, 2]);
    }

    public function testRelationHasManyFindExpectingSingleModelDoesntReturnACustomCollection(): ?Model
    {
        return (new Group)->accounts()->find(1);
    }

    /**
     * @phpstan-return AccountCollection<int, Account>
     */
    public function testRelationHasManyFindOrFailReturnsCustomCollection(): AccountCollection
    {
        return (new Group)->accounts()->findOrFail([1, 2]);
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testRelationHasManyFindOrFailReturnsEloquentCollection(): Collection
    {
        return (new User)->children()->findOrFail([1, 2]);
    }

    public function testRelationHasManyFindOrFailExpectingSingleModelDoesntReturnACustomCollection(): Model
    {
        return (new Group)->accounts()->findOrFail(1);
    }

    /**
     * @phpstan-return AccountCollection<int, Account>
     */
    public function testRelationHasManyFindManyReturnsCustomCollection(): AccountCollection
    {
        return (new Group)->accounts()->findMany([1, 2]);
    }

    /**
     * @phpstan-return Collection<int, User>
     */
    public function testRelationHasManyFindManyReturnsEloquentCollection(): Collection
    {
        return (new User)->children()->findMany([1, 2]);
    }

    // Collection...

    public function testCustomCollectionWhereReturnsCustomCollection(): void
    {
        assertType('App\AccountCollection<int, App\Account>', (new User)->accounts->where('active', true));
    }

    public function testCustomMethodInCustomCollection(User $user): void
    {
        assertType('App\AccountCollection<int, App\Account>', $user->accounts->filterByActive());
    }

    public function testEloquentCollectionWhereReturnsEloquentCollection(): void
    {
        assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', (new User)->children->where('active', true));
    }
}
