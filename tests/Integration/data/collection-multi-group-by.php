<?php

declare(strict_types=1);

namespace CollectionMultiGroupByIntegration;

use App\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * @param Collection<int, User> $users
 * @param list<string> $selectors
 */
function dynamicGrouping(Collection $users, array $selectors): void
{
    $users->groupBy($selectors)->load('accounts');
    $users->groupBy(['name', 'email'])->load('accounts');
}
