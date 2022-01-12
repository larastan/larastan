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

class CustomEloquentCollectionTest
{
    /**
     * @phpstan-return Collection<int, User>
     */
    public function testEloquentCollectionWhereReturnsEloquentCollection(): Collection
    {
        return (new User)->children->where('active', true);
    }
}
