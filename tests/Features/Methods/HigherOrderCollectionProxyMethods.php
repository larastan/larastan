<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use App\Account;
use App\Importer;
use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;
use function PHPStan\Testing\assertType;

class HigherOrderCollectionProxyMethods
{
    /** @var Collection<int, User> */
    public $users;

    public function testAverage(): float
    {
        return $this->users->avg->id() + $this->users->average->id();
    }

    public function testContains(): bool
    {
        return $this->users->contains->isActive();
    }

    /** @return Collection<int, User> */
    public function testEach(): Collection
    {
        return $this->users->each->delete();
    }

    public function testEvery(): bool
    {
        return $this->users->every->isActive();
    }

    /** @return Collection<int, User> */
    public function testFilter(): Collection
    {
        return $this->users->filter->isActive();
    }

    public function testFirst(): ?User
    {
        return $this->users->first->isActive();
    }

    /** @return SupportCollection<int, User> */
    public function testFlatMap(): SupportCollection
    {
        return $this->users->flatMap->isActive();
    }

    public function testGroupBy(): void
    {
        assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Database\Eloquent\Collection<int, App\User>>', $this->users->groupBy->isActive());
    }

    /** @return Collection<int, User> */
    public function testKeyBy(): Collection
    {
        return $this->users->keyBy->isActive();
    }

    /** @return SupportCollection<int, bool> */
    public function testMapWithBoolMethod(): SupportCollection
    {
        return $this->users->map->isActive();
    }

    /** @return SupportCollection<int, HasMany<Account>> */
    public function testMapWithRelationMethod(): SupportCollection
    {
        return $this->users->map->accounts();
    }

    /** @return SupportCollection<int, int> */
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

    public function testPartition(): void
    {
        assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Database\Eloquent\Collection<int, App\User>>', $this->users->partition->isActive());
    }

    /** @return Collection<int, User> */
    public function testReject(): Collection
    {
        return $this->users->reject->isActive();
    }

    /** @return Collection<int, User> */
    public function testSkipUntil(): Collection
    {
        return $this->users->skipUntil->isActive();
    }

    /** @return Collection<int, User> */
    public function testSkipWhile(): Collection
    {
        return $this->users->skipWhile->isActive();
    }

    public function testSum(): int
    {
        return $this->users->sum->id();
    }

    /** @return Collection<int, User> */
    public function testTakeUntil(): Collection
    {
        return $this->users->takeUntil->isActive();
    }

    /** @return Collection<int, User> */
    public function testTakeWhile(): Collection
    {
        return $this->users->takeWhile->isActive();
    }

    /** @return Collection<int, User> */
    public function testUnique(): Collection
    {
        return $this->users->unique->isActive();
    }

    /**
     * @param  SupportCollection<int, Importer>  $collection
     * @return SupportCollection<int, bool>
     */
    public function testMapWithSupportCollection(SupportCollection $collection): SupportCollection
    {
        return $collection->map->import();
    }

    /**
     * @param  SupportCollection<int, Importer>  $collection
     * @return SupportCollection<int, Importer>
     */
    public function testEachWithSupportCollection(SupportCollection $collection): SupportCollection
    {
        return $collection->each->import();
    }

    /**
     * @param  SupportCollection<int, User>  $collection
     * @return SupportCollection<int, User>
     */
    public function testKeyByWithSupportCollection(SupportCollection $collection): SupportCollection
    {
        return $collection->keyBy->getKey();
    }

    /**
     * @param  SupportCollection<int, Importer>  $collection
     * @return SupportCollection<int, Importer>
     */
    public function testFilterWithSupportCollection(SupportCollection $collection): SupportCollection
    {
        return $collection->filter->isImported();
    }
}
