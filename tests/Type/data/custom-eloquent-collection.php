<?php

namespace CustomEloquentCollection;

use App\Account;
use App\Group;
use App\ModelWithNonGenericCollection;
use App\ModelWithOnlyValueGenericCollection;
use App\Role;
use App\User;

use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('App\AccountCollection<int, App\Account>', Account::query()->fromQuery('select * from accounts'));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::query()->fromQuery('select * from accounts'));
    assertType('App\NonGenericCollection', ModelWithNonGenericCollection::query()->fromQuery('select * from accounts'));
    assertType('App\OnlyValueGenericCollection<App\ModelWithOnlyValueGenericCollection>', ModelWithOnlyValueGenericCollection::query()->fromQuery('select * from accounts'));

    assertType('App\AccountCollection<int, App\Account>', Account::fromQuery('select * from accounts'));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::fromQuery('select * from accounts'));
    assertType('App\NonGenericCollection', ModelWithNonGenericCollection::fromQuery('select * from accounts'));
    assertType('App\OnlyValueGenericCollection<App\ModelWithOnlyValueGenericCollection>', ModelWithOnlyValueGenericCollection::fromQuery('select * from accounts'));

    assertType('App\AccountCollection<int, App\Account>', Account::query()->hydrate([['active' => 1], ['active' => 0]]));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::query()->hydrate([['active' => 1], ['active' => 0]]));
    assertType('App\NonGenericCollection', ModelWithNonGenericCollection::query()->hydrate([['active' => 1], ['active' => 0]]));
    assertType('App\OnlyValueGenericCollection<App\ModelWithOnlyValueGenericCollection>', ModelWithOnlyValueGenericCollection::query()->hydrate([['active' => 1], ['active' => 0]]));

    assertType('App\AccountCollection<int, App\Account>', Account::hydrate([['active' => 1], ['active' => 0]]));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::hydrate([['active' => 1], ['active' => 0]]));
    assertType('App\NonGenericCollection', ModelWithNonGenericCollection::hydrate([['active' => 1], ['active' => 0]]));
    assertType('App\OnlyValueGenericCollection<App\ModelWithOnlyValueGenericCollection>', ModelWithOnlyValueGenericCollection::hydrate([['active' => 1], ['active' => 0]]));

    assertType('App\AccountCollection<int, App\Account>', Account::query()->find([1, 2]));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::query()->find([1, 2]));
    assertType('App\NonGenericCollection', ModelWithNonGenericCollection::query()->find([1, 2]));
    assertType('App\OnlyValueGenericCollection<App\ModelWithOnlyValueGenericCollection>', ModelWithOnlyValueGenericCollection::query()->find([1, 2]));

    assertType('App\AccountCollection<int, App\Account>', Account::find([1, 2]));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::find([1, 2]));
    assertType('App\NonGenericCollection', ModelWithNonGenericCollection::find([1, 2]));
    assertType('App\OnlyValueGenericCollection<App\ModelWithOnlyValueGenericCollection>', ModelWithOnlyValueGenericCollection::find([1, 2]));

    assertType('App\AccountCollection<int, App\Account>', Account::query()->findMany([1, 2]));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::query()->findMany([1, 2]));
    assertType('App\NonGenericCollection', ModelWithNonGenericCollection::query()->findMany([1, 2]));
    assertType('App\OnlyValueGenericCollection<App\ModelWithOnlyValueGenericCollection>', ModelWithOnlyValueGenericCollection::query()->findMany([1, 2]));

    assertType('App\AccountCollection<int, App\Account>', Account::findMany([1, 2]));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::findMany([1, 2]));
    assertType('App\NonGenericCollection', ModelWithNonGenericCollection::findMany([1, 2]));
    assertType('App\OnlyValueGenericCollection<App\ModelWithOnlyValueGenericCollection>', ModelWithOnlyValueGenericCollection::findMany([1, 2]));

    assertType('App\AccountCollection<int, App\Account>', Account::query()->findOrFail([1, 2]));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::query()->findOrFail([1, 2]));
    assertType('App\NonGenericCollection', ModelWithNonGenericCollection::query()->findOrFail([1, 2]));
    assertType('App\OnlyValueGenericCollection<App\ModelWithOnlyValueGenericCollection>', ModelWithOnlyValueGenericCollection::query()->findOrFail([1, 2]));

    assertType('App\AccountCollection<int, App\Account>', Account::findOrFail([1, 2]));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::findOrFail([1, 2]));
    assertType('App\NonGenericCollection', ModelWithNonGenericCollection::findOrFail([1, 2]));
    assertType('App\OnlyValueGenericCollection<App\ModelWithOnlyValueGenericCollection>', ModelWithOnlyValueGenericCollection::findOrFail([1, 2]));

    assertType('App\AccountCollection<int, App\Account>', Account::query()->get());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::query()->get());
    assertType('App\NonGenericCollection', ModelWithNonGenericCollection::query()->get());
    assertType('App\OnlyValueGenericCollection<App\ModelWithOnlyValueGenericCollection>', ModelWithOnlyValueGenericCollection::query()->get());

    assertType('App\AccountCollection<int, App\Account>', Account::get());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::get());
    assertType('App\NonGenericCollection', ModelWithNonGenericCollection::get());
    assertType('App\OnlyValueGenericCollection<App\ModelWithOnlyValueGenericCollection>', ModelWithOnlyValueGenericCollection::get());

    assertType('App\AccountCollection<int, App\Account>', Account::all());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', User::all());
    assertType('App\NonGenericCollection', ModelWithNonGenericCollection::all());
    assertType('App\OnlyValueGenericCollection<App\ModelWithOnlyValueGenericCollection>', ModelWithOnlyValueGenericCollection::all());

    assertType('App\AccountCollection<int, App\Account>', (new User)->accounts()->get());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', (new User)->children()->get());
    assertType('App\NonGenericCollection', (new User)->modelsWithNonGenericCollection()->get());
    assertType('App\OnlyValueGenericCollection<App\ModelWithOnlyValueGenericCollection>', (new User)->modelsWithOnlyValueGenericCollection()->get());

    assertType('App\AccountCollection<int, App\Account>', (new User)->accounts()->getEager());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', (new User)->children()->getEager());
    assertType('App\NonGenericCollection', (new User)->modelsWithNonGenericCollection()->getEager());
    assertType('App\OnlyValueGenericCollection<App\ModelWithOnlyValueGenericCollection>', (new User)->modelsWithOnlyValueGenericCollection()->getEager());

    assertType('App\AccountCollection<int, App\Account>', (new User)->accounts()->createMany([]));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', (new User)->children()->createMany([]));
    assertType('App\NonGenericCollection', (new User)->modelsWithNonGenericCollection()->createMany([]));
    assertType('App\OnlyValueGenericCollection<App\ModelWithOnlyValueGenericCollection>', (new User)->modelsWithOnlyValueGenericCollection()->createMany([]));

    assertType('App\AccountCollection<int, App\Account>', (new User)->accounts()->active()->get());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', (new User)->children()->active()->get());

    assertType('App\AccountCollection<int, App\Account>', (new User)->accounts);
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', (new User)->children);
    assertType('App\NonGenericCollection', (new User)->modelsWithNonGenericCollection);
    assertType('App\OnlyValueGenericCollection<App\ModelWithOnlyValueGenericCollection>', (new User)->modelsWithOnlyValueGenericCollection);

    assertType('App\RoleCollection<int, App\Role&object{pivot: Illuminate\Database\Eloquent\Relations\Pivot}>', (new User)->roles()->find([1, 2]));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User&object{pivot: Illuminate\Database\Eloquent\Relations\Pivot}>', (new Role)->users()->find([1, 2]));

    assertType('(App\Role&object{pivot: Illuminate\Database\Eloquent\Relations\Pivot})|null', (new User)->roles()->find(1));
    assertType('App\RoleCollection<int, App\Role&object{pivot: Illuminate\Database\Eloquent\Relations\Pivot}>', (new User)->roles()->findOrFail([1, 2]));

    assertType('Illuminate\Database\Eloquent\Collection<int, App\User&object{pivot: Illuminate\Database\Eloquent\Relations\Pivot}>', (new Role)->users()->findOrFail([1, 2]));
    assertType('App\Role&object{pivot: Illuminate\Database\Eloquent\Relations\Pivot}', (new User)->roles()->findOrFail(1));

    assertType('App\RoleCollection<int, App\Role&object{pivot: Illuminate\Database\Eloquent\Relations\Pivot}>', (new User)->roles()->findMany([1, 2]));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User&object{pivot: Illuminate\Database\Eloquent\Relations\Pivot}>', (new Role)->users()->findMany([1, 2]));

    assertType('App\TransactionCollection<int, App\Transaction>', (new User)->transactions()->find([1, 2]));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User&object{pivot: Illuminate\Database\Eloquent\Relations\Pivot}>', (new Role)->users()->find([1, 2]));
    assertType('App\Transaction|null', (new User)->transactions()->find(1));
    assertType('App\TransactionCollection<int, App\Transaction>', (new User)->transactions()->findOrFail([1, 2]));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User&object{pivot: Illuminate\Database\Eloquent\Relations\Pivot}>', (new Role)->users()->findOrFail([1, 2]));
    assertType('App\Transaction', (new User)->transactions()->findOrFail(1));
    assertType('App\TransactionCollection<int, App\Transaction>', (new User)->transactions()->findMany([1, 2]));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User&object{pivot: Illuminate\Database\Eloquent\Relations\Pivot}>', (new Role)->users()->findMany([1, 2]));
    assertType('App\AccountCollection<int, App\Account>', (new Group)->accounts()->find([1, 2]));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', (new User)->children()->find([1, 2]));
    assertType('App\Account|null', (new Group)->accounts()->find(1));
    assertType('App\AccountCollection<int, App\Account>', (new Group)->accounts()->findOrFail([1, 2]));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', (new User)->children()->findOrFail([1, 2]));
    assertType('App\Account', (new Group)->accounts()->findOrFail(1));
    assertType('App\AccountCollection<int, App\Account>', (new Group)->accounts()->findMany([1, 2]));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', (new User)->children()->findMany([1, 2]));
    assertType('App\AccountCollection<int, App\Account>', (new User)->accounts->where('active', true));
    assertType('App\AccountCollection<int, App\Account>', (new User)->accounts->filterByActive());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', (new User)->children->where('active', true));
}
