<?php

namespace CollectionOfType;

use App\Account;
use App\AccountCollection;
use App\Transaction;
use App\User;
use Illuminate\Database\Eloquent\Collection;

class CollectionOfTypeTest
{
    /**
     * @phpstan-param collection-of<User> $users
     */
    public function acceptsUserCollection(Collection $users): void
    {
    }

    /**
     * @phpstan-param collection-of<Account> $accounts
     */
    public function acceptsAccountCollection(Collection $accounts): void
    {
    }

    public function testValidUsage(): void
    {
        $this->acceptsUserCollection(User::all());
        $this->acceptsAccountCollection(Account::all());
    }

    public function testInvalidUsage(): void
    {
        //passing a User collection to a method-expecting Account collection
        $this->acceptsAccountCollection(User::all());

        //passing an Account collection to the method expecting a User collection
        $this->acceptsUserCollection(Account::all());
    }
}

class GenericTest {
    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param class-string<TModel> $modelClass
     *
     * @return collection-of<TModel>
     */
    function getAllModels(string $modelClass): Collection
    {
        return $modelClass::all();
    }

    /**
     * @param AccountCollection<array-key, Account> $collection
     */
    function acceptsAccountCollection(AccountCollection $collection): void
    {

    }
}

function testGenericCollectionHandler(GenericTest $generic): void
{
    $userCollection = $generic->getAllModels(User::class);
    $accountCollection = $generic->getAllModels(Account::class);

    $generic->acceptsAccountCollection($userCollection);
    $generic->acceptsAccountCollection($accountCollection);
}
