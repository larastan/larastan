<?php

declare(strict_types=1);

namespace PaginatorExtension;

use App\Account;
use App\AccountCollection;
use App\ModelWithNonGenericCollection;
use App\ModelWithOnlyValueGenericCollection;
use App\User;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('Illuminate\Pagination\LengthAwarePaginator<int, App\User>', User::paginate());
    assertType('array<int, App\User>', User::paginate()->all());
    assertType('array<int, App\User>', User::paginate()->items());
    assertType('App\User|null', User::paginate()[0]);

    assertType('Illuminate\Pagination\Paginator<int, App\User>', User::simplePaginate());
    assertType('array<int, App\User>', User::simplePaginate()->all());
    assertType('array<int, App\User>', User::simplePaginate()->items());
    assertType('App\User|null', User::simplePaginate()[0]);

    assertType('Illuminate\Pagination\CursorPaginator<int, App\User>', User::cursorPaginate());
    assertType('array<int, App\User>', User::cursorPaginate()->all());
    assertType('array<int, App\User>', User::cursorPaginate()->items());
    assertType('App\User|null', User::cursorPaginate()[0]);

    assertType('ArrayIterator<int, App\User>', User::query()->paginate()->getIterator());

    // HasMany
    assertType('Illuminate\Pagination\LengthAwarePaginator<int, App\Account>', (new User())->accounts()->paginate());

    // BelongsToMany
    assertType('Illuminate\Pagination\LengthAwarePaginator<int, App\Post&object{pivot: Illuminate\Database\Eloquent\Relations\Pivot}>', (new User())->posts()->paginate());
}

function paginateCollections(): void
{
    assertType('Illuminate\Database\Eloquent\Collection<(int|string), App\User>', User::query()->paginate()->getCollection());
    assertType('App\AccountCollection<(int|string), App\Account>', Account::query()->paginate()->getCollection());
    assertType('App\NonGenericCollection', ModelWithNonGenericCollection::query()->paginate()->getCollection());
    assertType('App\OnlyValueGenericCollection<App\ModelWithOnlyValueGenericCollection>', ModelWithOnlyValueGenericCollection::query()->paginate()->getCollection());
    assertType('Illuminate\Support\Collection<int, stdClass>', (new LengthAwarePaginator([new \stdClass()], 1, 15))->getCollection());

    $paginator = User::query()->paginate();
    $paginator->setCollection(new AccountCollection(['account' => new Account()]));
    assertType('App\AccountCollection<(int|string), App\Account>', $paginator->getCollection());
    assertType('Illuminate\Pagination\LengthAwarePaginator<string, App\Account>', $paginator);
    assertType('App\AccountCollection<(int|string), App\Account>', $paginator->getCollection()->filterByActive());

    $paginator->setCollection(new Collection(['name' => 'Taylor']));
    assertType('Illuminate\Support\Collection<string, string>', $paginator->getCollection());
}

function simplePaginateCollections(): void
{
    assertType('Illuminate\Database\Eloquent\Collection<(int|string), App\User>', User::query()->simplePaginate()->getCollection());
    assertType('App\AccountCollection<(int|string), App\Account>', Account::query()->simplePaginate()->getCollection());
    assertType('App\NonGenericCollection', ModelWithNonGenericCollection::query()->simplePaginate()->getCollection());
    assertType('App\OnlyValueGenericCollection<App\ModelWithOnlyValueGenericCollection>', ModelWithOnlyValueGenericCollection::query()->simplePaginate()->getCollection());
    assertType('Illuminate\Support\Collection<int, stdClass>', (new Paginator([new \stdClass()], 15))->getCollection());

    $paginator = User::query()->simplePaginate();
    $paginator->setCollection(new AccountCollection(['account' => new Account()]));
    assertType('App\AccountCollection<(int|string), App\Account>', $paginator->getCollection());
    assertType('Illuminate\Pagination\Paginator<string, App\Account>', $paginator);
    assertType('App\AccountCollection<(int|string), App\Account>', $paginator->getCollection()->filterByActive());

    $paginator->setCollection(new Collection(['name' => 'Taylor']));
    assertType('Illuminate\Support\Collection<string, string>', $paginator->getCollection());
}

function cursorPaginateCollections(): void
{
    assertType('Illuminate\Database\Eloquent\Collection<(int|string), App\User>', User::query()->cursorPaginate()->getCollection());
    assertType('App\AccountCollection<(int|string), App\Account>', Account::query()->cursorPaginate()->getCollection());
    assertType('App\NonGenericCollection', ModelWithNonGenericCollection::query()->cursorPaginate()->getCollection());
    assertType('App\OnlyValueGenericCollection<App\ModelWithOnlyValueGenericCollection>', ModelWithOnlyValueGenericCollection::query()->cursorPaginate()->getCollection());
    assertType('Illuminate\Support\Collection<int, stdClass>', (new CursorPaginator([new \stdClass()], 15))->getCollection());

    $paginator = User::query()->cursorPaginate();
    $paginator->setCollection(new AccountCollection(['account' => new Account()]));
    assertType('App\AccountCollection<(int|string), App\Account>', $paginator->getCollection());
    assertType('Illuminate\Pagination\CursorPaginator<string, App\Account>', $paginator);
    assertType('App\AccountCollection<(int|string), App\Account>', $paginator->getCollection()->filterByActive());

    $paginator->setCollection(new Collection(['name' => 'Taylor']));
    assertType('Illuminate\Support\Collection<string, string>', $paginator->getCollection());
}

/**
 * @param LengthAwarePaginator<string, array{id: int}> $arrays
 * @param Paginator<int, int> $integers
 * @param CursorPaginator<string, string> $strings
 * @param LengthAwarePaginator<int, Account|null> $nullable
 * @param CursorPaginator<int, Account|User> $union
 * @param Paginator<string, Account&\Countable> $intersection
 */
function collectionValueTypes(LengthAwarePaginator $arrays, Paginator $integers, CursorPaginator $strings, LengthAwarePaginator $nullable, CursorPaginator $union, Paginator $intersection): void
{
    assertType('Illuminate\Support\Collection<string, array{id: int}>', $arrays->getCollection());
    assertType('Illuminate\Support\Collection<int, int>', $integers->getCollection());
    assertType('Illuminate\Support\Collection<string, string>', $strings->getCollection());
    assertType('App\Account|null', $nullable->getCollection()->first());
    assertType('App\AccountCollection<(int|string), App\Account>|Illuminate\Database\Eloquent\Collection<(int|string), App\User>', $union->getCollection());
    assertType('App\AccountCollection<(int|string), App\Account&Countable>', $intersection->getCollection());
}
