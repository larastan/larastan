<?php

declare(strict_types=1);

namespace Tests\Rules\Data;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\LazyCollection;

/**
 * @param  Collection<int, int>  $ints
 * @param  EloquentCollection<int, Model>  $models
 * @param  LazyCollection<int, Model|string>  $innerUnion
 * @param  EloquentCollection<int, Model>|Collection<string, string>  $outerUnion
 */
function test(
    mixed $unknown,
    Collection $ints,
    EloquentCollection $models,
    LazyCollection $innerUnion,
    Enumerable $outerUnion,
): void {
    // should be reported
    $ints->toArray();

    // should not be reported
    $unknown->toArray();
    $models->toArray();
    $innerUnion->toArray();
    $outerUnion->toArray();
}
