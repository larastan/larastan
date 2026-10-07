<?php

namespace BleedingEdgeEloquentCollectionMap;

use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

use function PHPStan\Testing\assertType;

/** @param EloquentCollection<int, User> $users */
function mappedTypesAreInferredFromUsages(EloquentCollection $users): void
{
    $labels = $users->map(fn ($user) => 'unknown');
    $labels->push('other');
    assertType("Illuminate\Support\Collection<int, 'other'|'unknown'>", $labels);
}
