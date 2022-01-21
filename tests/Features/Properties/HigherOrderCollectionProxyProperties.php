<?php

declare(strict_types=1);

namespace Tests\Features\Properties;

use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use function PHPStan\Testing\assertType;

class HigherOrderCollectionProxyProperties
{
    /** @var Collection<int, User> */
    public $users;

    public function testAverage(): float
    {
        return $this->users->avg->id + $this->users->average->id;
    }

    public function testContains(): bool
    {
        return $this->users->contains->email;
    }

    /** @return Collection<int, User> */
    public function testEach(): Collection
    {
        // Does not make too much sense, but it should work
        return $this->users->each->email;
    }

    public function testEvery(): bool
    {
        // Does not make too much sense, but it should work
        return $this->users->every->email;
    }

    /** @return Collection<int, User> */
    public function testFilter(): Collection
    {
        // Does not make too much sense, but it should work
        return $this->users->filter->email;
    }

    public function testFirst(): ?User
    {
        return $this->users->first->email;
    }

    /** @return \Illuminate\Support\Collection<int, mixed> */
    public function testFlatMap(): \Illuminate\Support\Collection
    {
        // Does not make too much sense, but it should work
        return $this->users->flatMap->email;
    }

    public function testGroupBy(): void
    {
        assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Database\Eloquent\Collection<int, App\User>>', $this->users->groupBy->email);
    }

    public function testKeyBy(): void
    {
        // Does not make too much sense, but it should work
        assertType('Illuminate\Database\Eloquent\Collection<(int|string), App\User>', $this->users->keyBy->email);
    }

    /** @return \Illuminate\Support\Collection<int, string> */
    public function testMapWithStringProperty(): \Illuminate\Support\Collection
    {
        // Does not make too much sense, but it should work
        return $this->users->map->email;
    }

    /** @return \Illuminate\Support\Collection<int, ?Carbon> */
    public function testMapWithCarbonProperty(): \Illuminate\Support\Collection
    {
        // Does not make too much sense, but it should work
        return $this->users->map->created_at;
    }

    /** @return \Illuminate\Support\Collection<int, int> */
    public function testMapWithIntegerProperty(): \Illuminate\Support\Collection
    {
        // Does not make too much sense, but it should work
        return $this->users->map->id;
    }

    public function testMaxWithStringProperty(): string
    {
        return $this->users->max->email;
    }

    public function testMaxWithIntegerProperty(): int
    {
        return $this->users->max->id;
    }

    public function testMaxWithCarbonProperty(): ?Carbon
    {
        return $this->users->max->created_at;
    }

    public function testMinWithStringProperty(): string
    {
        return $this->users->min->email;
    }

    public function testMinWithIntegerProperty(): int
    {
        return $this->users->min->id;
    }

    public function testMinWithCarbonProperty(): ?Carbon
    {
        return $this->users->min->created_at;
    }

    public function testPartition(): void
    {
        assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Database\Eloquent\Collection<int, App\User>>', $this->users->partition->email);
    }

    /** @return Collection<int, User> */
    public function testReject(): Collection
    {
        return $this->users->reject->email;
    }

    /** @return Collection<int, User> */
    public function testSkipUntil(): Collection
    {
        return $this->users->skipUntil->email;
    }

    /** @return Collection<int, User> */
    public function testSkipWhile(): Collection
    {
        return $this->users->skipWhile->email;
    }

    public function testSum(): int
    {
        return $this->users->sum->id;
    }

    /** @return Collection<int, User> */
    public function testTakeUntil(): Collection
    {
        return $this->users->takeUntil->email;
    }

    /** @return Collection<int, User> */
    public function testTakeWhile(): Collection
    {
        return $this->users->takeWhile->email;
    }

    /** @return Collection<int, User> */
    public function testUnique(): Collection
    {
        return $this->users->unique->email;
    }
}
