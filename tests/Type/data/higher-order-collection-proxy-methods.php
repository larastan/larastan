<?php

namespace HigherOrderCollectionProxyMethods;

use App\Importer;
use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use function PHPStan\Testing\assertType;

/**
 * @param  Collection<int, User>  $users
 * @param  SupportCollection<int, Importer>  $collection
 */
function doFoo(Collection $users, User $user, SupportCollection $collection)
{
    assertType('float', $users->avg->id() + $users->average->id());
    assertType('bool', $users->contains->isActive());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $users->each->delete());
    assertType('bool', $users->every->isActive());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $users->filter->isActive());
    assertType('App\User|null', $users->first->isActive());
    assertType('Illuminate\Support\Collection<int, mixed>', $users->flatMap->isActive());
    assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Database\Eloquent\Collection<int, App\User>>', $users->groupBy->isActive());
    assertType('Illuminate\Database\Eloquent\Collection<(int|string), App\User>', $users->keyBy->isActive());
    assertType('Illuminate\Support\Collection<int, bool>', $users->map->isActive());
    assertType('Illuminate\Support\Collection<int, Illuminate\Database\Eloquent\Relations\HasMany<App\Account>>', $users->map->accounts());
    assertType('Illuminate\Support\Collection<int, int>', $users->map->id());
    assertType('array<int, array>', $user->accounts->map->getAttributes()->all());
    assertType('int', $users->max->id());
    assertType('int', $users->min->id());
    assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Database\Eloquent\Collection<int, App\User>>', $users->partition->isActive());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $users->reject->isActive());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $users->skipUntil->isActive());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $users->skipWhile->isActive());
    assertType('int', $users->sum->id());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $users->takeUntil->isActive());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $users->takeWhile->isActive());
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $users->unique->isActive());
    assertType('Illuminate\Support\Collection<int, bool>', $collection->map->import());
    assertType('Illuminate\Support\Collection<int, App\Importer>', $collection->each->import());
    assertType('Illuminate\Support\Collection<int, App\Importer>', $collection->filter->isImported());

    assertType('float', $users->avg->id + $users->average->id);
    assertType('bool', $users->contains->email);
    assertType('bool', $users->every->email);
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $users->each->email);
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $users->filter->email);
    assertType('Illuminate\Support\Collection<int, mixed>', $users->flatMap->email);
    assertType('App\User|null', $users->first->email);
    assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Database\Eloquent\Collection<int, App\User>>', $users->groupBy->email);
    assertType('Illuminate\Database\Eloquent\Collection<(int|string), App\User>', $users->keyBy->email);
    assertType('Illuminate\Support\Collection<int, string>', $users->map->email);
    assertType('Illuminate\Support\Collection<int, int>', $users->map->id);
    assertType('Illuminate\Support\Collection<int, Carbon\Carbon|null>', $users->map->created_at);
    assertType('string', $users->max->email);
    assertType('int', $users->max->id);
    assertType('string', $users->min->email);
    assertType('int', $users->min->id);
    assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Database\Eloquent\Collection<int, App\User>>', $users->partition->email);
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $users->reject->email);
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $users->skipUntil->email);
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $users->skipWhile->email);
    assertType('int', $users->sum->id);
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $users->takeUntil->email);
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $users->takeWhile->email);
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $users->unique->email);
}
