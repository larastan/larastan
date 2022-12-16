<?php

namespace HigherOrderCollectionProxyMethods;

use App\Importer;
use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use function PHPStan\Testing\assertType;

class HigherOrderCollectionProxyMethods
{
    /** @var Collection<int, User> */
    public $users;

    public function testAverage()
    {
        assertType('float', $this->users->avg->id() + $this->users->average->id());
    }

    public function testContains()
    {
        assertType('bool', $this->users->contains->isActive());
    }

    public function testEach()
    {
        assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $this->users->each->delete());
    }

    public function testEvery()
    {
        assertType('bool', $this->users->every->isActive());
    }

    public function testFilter()
    {
        assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $this->users->filter->isActive());
    }

    public function testFirst()
    {
        assertType('App\User|null', $this->users->first->isActive());
    }

    public function testFlatMap()
    {
        assertType('Illuminate\Support\Collection<int, mixed>', $this->users->flatMap->isActive());
    }

    public function testGroupBy()
    {
        assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Database\Eloquent\Collection<int, App\User>>', $this->users->groupBy->isActive());
    }

    public function testKeyBy()
    {
        assertType('Illuminate\Database\Eloquent\Collection<(int|string), App\User>', $this->users->keyBy->isActive());
    }

    public function testMapWithBoolMethod()
    {
        assertType('Illuminate\Support\Collection<int, bool>', $this->users->map->isActive());
    }

    public function testMapWithRelationMethod()
    {
        assertType('Illuminate\Support\Collection<int, Illuminate\Database\Eloquent\Relations\HasMany<App\Account>>', $this->users->map->accounts());
    }

    public function testMapWithIntegerMethod()
    {
        assertType('Illuminate\Support\Collection<int, int>', $this->users->map->id());
    }

    public function testMapOnRelation(User $user)
    {
        assertType('array<int, array>', $user->accounts->map->getAttributes()->all());
    }

    public function testMax()
    {
        assertType('int', $this->users->max->id());
    }

    public function testMin()
    {
        assertType('int', $this->users->min->id());
    }

    public function testPartition()
    {
        assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Database\Eloquent\Collection<int, App\User>>', $this->users->partition->isActive());
    }

    public function testReject()
    {
        assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $this->users->reject->isActive());
    }

    public function testSkipUntil()
    {
        assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $this->users->skipUntil->isActive());
    }

    public function testSkipWhile()
    {
        assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $this->users->skipWhile->isActive());
    }

    public function testSum()
    {
        assertType('int', $this->users->sum->id());
    }

    public function testTakeUntil()
    {
        assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $this->users->takeUntil->isActive());
    }

    public function testTakeWhile()
    {
        assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $this->users->takeWhile->isActive());
    }

    public function testUnique()
    {
        assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', $this->users->unique->isActive());
    }

    /**
     * @param  SupportCollection<int, Importer>  $collection
     */
    public function testMapWithSupportCollection(SupportCollection $collection)
    {
        assertType('Illuminate\Support\Collection<int, bool>', $collection->map->import());
    }

    /**
     * @param  SupportCollection<int, Importer>  $collection
     */
    public function testEachWithSupportCollection(SupportCollection $collection)
    {
        assertType('Illuminate\Support\Collection<int, App\Importer>', $collection->each->import());
    }

    /**
     * @param  SupportCollection<int, User>  $collection
     */
    public function testKeyByWithSupportCollection(SupportCollection $collection)
    {
        assertType('Illuminate\Database\Eloquent\Collection<(int|string), App\User>', $collection->keyBy->getKey());
    }

    /**
     * @param  SupportCollection<int, Importer>  $collection
     */
    public function testFilterWithSupportCollection(SupportCollection $collection)
    {
        assertType('Illuminate\Support\Collection<int, App\Importer>', $collection->filter->isImported());
    }
}
