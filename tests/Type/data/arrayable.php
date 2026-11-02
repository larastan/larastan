<?php

namespace Arrayable;

use App\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

use function PHPStan\Testing\assertType;

/** @implements Arrayable<string, int> */
class Counts implements Arrayable
{
    public function toArray(): array
    {
        return ['total' => 1];
    }
}

/** @implements Arrayable<string, int> */
class Totals implements Arrayable
{
    /** @return array{total: int} */
    public function toArray(): array
    {
        return ['total' => 1];
    }
}

/**
 * @param Arrayable<int, string> $arrayable
 * @param Collection<int, User> $users
 */
function test(Counts $counts, Totals $totals, Arrayable $arrayable, Collection $users): void
{
    assertType('array<string, int>', $counts->toArray());
    assertType('array{total: int}', $totals->toArray());
    assertType('array<int, string>', $arrayable->toArray());
    assertType('array<int, mixed>', $users->toArray());
}
