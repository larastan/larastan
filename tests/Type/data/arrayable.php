<?php

namespace ArrayableTypes;

use App\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Arrayable as ArrayContract;
use Illuminate\Support\Collection;

use function PHPStan\Testing\assertType;

/** @implements Arrayable<array{score: int, label?: string}> */
final class Scores implements Arrayable
{
    public function toArray(): array
    {
        return ['score' => 42];
    }
}

/** @implements Arrayable<string, int> */
final class LegacyScores implements Arrayable
{
    public function toArray(): array
    {
        return ['score' => 42];
    }
}

/**
 * @param Arrayable<string, int> $legacy
 * @param Arrayable<array{score: int, label?: string}> $shaped
 * @param Arrayable<list<string>> $list
 */
function types(Arrayable $legacy, Arrayable $shaped, Arrayable $list): void
{
    assertType('array<string, int>', $legacy->toArray());
    assertType('array{score: int, label?: string}', $shaped->toArray());
    assertType('list<string>', $list->toArray());
    assertType('array{score: int, label?: string}', (new Scores())->toArray());
    assertType('array<string, int>', (new LegacyScores())->toArray());
    assertType('Illuminate\Support\Collection<string, int>', collect($legacy));
    assertType('Illuminate\Support\Collection<string, int|string>', collect($shaped));
    assertType('Illuminate\Support\Collection<int, string>', collect($list));
    assertType('Illuminate\Support\Collection<string, int>', new Collection($legacy));
    assertType('Illuminate\Support\Collection<string, int|string>', new Collection($shaped));
    assertType('Illuminate\Support\Collection<(int|string), int>', collect(['score'])->combine($legacy));
}

/**
 * @template TKey of array-key
 * @template TValue
 * @param Arrayable<TKey, TValue> $legacy
 * @return Arrayable<array<TKey, TValue>>
 */
function upgrade(Arrayable $legacy): Arrayable
{
    return $legacy;
}

/** @param Arrayable<array<string, int>> $shaped */
function compatible(Arrayable $shaped): void
{
    assertType('array<string, int>', upgrade($shaped)->toArray());
    assertType('array<string, int>', upgrade(new LegacyScores())->toArray());
    assertType('array<string, int>', upgrade(collect(['score' => 42]))->toArray());
}

/**
 * @param ArrayContract<*, int> $unknownKeys
 * @param Arrayable<string, *> $unknownValues
 * @param Arrayable $untyped
 * @param Arrayable<array{score: int}>|null $nullable
 */
function boundaries(Arrayable $unknownKeys, Arrayable $unknownValues, Arrayable $untyped, ?Arrayable $nullable): void
{
    assertType('array<int>', $unknownKeys->toArray());
    assertType('array<string, mixed>', $unknownValues->toArray());
    assertType('array<mixed>', $untyped->toArray());
    assertType('array{score: int}|null', $nullable?->toArray());
    assertType('array<int, mixed>', collect([new Scores()])->toArray());
    assertType('array<mixed>', (new \Illuminate\Pagination\Paginator([new Scores()], 10))->toArray());
    $objects = collect([new Scores()]);
    assertType('Illuminate\Support\Collection<int, ArrayableTypes\Scores>', collect($objects));
    assertType('Illuminate\Support\Collection<int, ArrayableTypes\Scores>', new Collection($objects));
    assertType('Illuminate\Support\Collection<int, ArrayableTypes\Scores>', Collection::make($objects));
}

/** @implements Arrayable<string, int> */
final class Counts implements Arrayable
{
    public function toArray(): array
    {
        return ['total' => 1];
    }
}

/** @implements Arrayable<string, int> */
final class Totals implements Arrayable
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
function implementations(Counts $counts, Totals $totals, Arrayable $arrayable, Collection $users): void
{
    assertType('array<string, int>', $counts->toArray());
    assertType('array{total: int}', $totals->toArray());
    assertType('array<int, string>', $arrayable->toArray());
    assertType('array<int, mixed>', $users->toArray());
}
