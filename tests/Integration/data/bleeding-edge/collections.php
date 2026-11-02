<?php

namespace BleedingEdgeCollectionsIntegration;

use Illuminate\Support\Collection;

/** @param Collection<int, int> $ints */
function takeInts(Collection $ints): void
{
}

/** @return Collection<int, string> */
function names(): Collection
{
    $names = collect();
    $names->push('Taylor');

    return $names;
}

function queries(int $id): bool
{
    $ids = collect([1, 2, 3]);

    return $ids->contains($id) || $ids->some($id) || $ids->partition($id)->isNotEmpty() || $ids->reject($id)->isEmpty();
}

function wrongValue(): void
{
    $ints = collect();
    $ints->push('one');
    takeInts($ints);
}
