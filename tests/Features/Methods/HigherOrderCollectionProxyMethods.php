<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class HigherOrderCollectionProxyMethods
{
    /** @var Collection<User> */
    public $users;

    public function testAverage(): float
    {
        return $this->users->avg->id() + $this->users->average->id();
    }

    public function testContains(): bool
    {
        return $this->users->contains->isActive();
    }

    /** @return Collection<User> */
    public function testEach(): Collection
    {
        return $this->users->each->delete();
    }

    public function testEvery(): bool
    {
        return $this->users->every->isActive();
    }

    /** @return Collection<User> */
    public function testFilter(): Collection
    {
        return $this->users->filter->isActive();
    }

    public function testFirst(): ?User
    {
        return $this->users->first->isActive();
    }

    /** @return SupportCollection */
    public function testFlatMap(): SupportCollection
    {
        return $this->users->flatMap->isActive();
    }

    /** @return Collection<Collection<User>> */
    public function testGroupBy(): Collection
    {
        return $this->users->groupBy->isActive();
    }

    /** @return Collection<User> */
    public function testKeyBy(): Collection
    {
        return $this->users->keyBy->isActive();
    }

    /** @return SupportCollection */
    public function testMapWithBoolMethod(): SupportCollection
    {
        return $this->users->map->isActive();
    }

    /** @return SupportCollection */
    public function testMapWithRelationMethod(): SupportCollection
    {
        return $this->users->map->accounts();
    }

    /** @return SupportCollection */
    public function testMapWithIntegerMethod(): SupportCollection
    {
        return $this->users->map->id();
    }

    /** @return array<mixed> */
    public function testMapOnRelation(User $user): array
    {
        return $user->accounts->map->getAttributes()->all();
    }

    public function testMax(): int
    {
        return $this->users->max->id();
    }

    public function testMin(): int
    {
        return $this->users->min->id();
    }

    /** @return Collection<Collection<User>> */
    public function testPartition(): Collection
    {
        return $this->users->partition->isActive();
    }

    /** @return Collection<User> */
    public function testReject(): Collection
    {
        return $this->users->reject->isActive();
    }

    /** @return Collection<User> */
    public function testSkipUntil(): Collection
    {
        return $this->users->skipUntil->isActive();
    }

    /** @return Collection<User> */
    public function testSkipWhile(): Collection
    {
        return $this->users->skipWhile->isActive();
    }

    public function testSum(): int
    {
        return $this->users->sum->id();
    }

    /** @return Collection<User> */
    public function testTakeUntil(): Collection
    {
        return $this->users->takeUntil->isActive();
    }

    /** @return Collection<User> */
    public function testTakeWhile(): Collection
    {
        return $this->users->takeWhile->isActive();
    }

    /** @return Collection<User> */
    public function testUnique(): Collection
    {
        return $this->users->unique->isActive();
    }

    /**
     * @param  SupportCollection  $collection
     * @return SupportCollection
     */
    public function testMapWithSupportCollection(SupportCollection $collection): SupportCollection
    {
        return $collection->map->import();
    }

    /**
     * @param  SupportCollection  $collection
     * @return SupportCollection
     */
    public function testEachWithSupportCollection(SupportCollection $collection): SupportCollection
    {
        return $collection->each->import();
    }

    /**
     * @param  SupportCollection  $collection
     * @return SupportCollection
     */
    public function testKeyByWithSupportCollection(SupportCollection $collection): SupportCollection
    {
        return $collection->keyBy->getKey();
    }

    /**
     * @param  SupportCollection  $collection
     * @return SupportCollection
     */
    public function testFilterWithSupportCollection(SupportCollection $collection): SupportCollection
    {
        return $collection->filter->isImported();
    }
}
