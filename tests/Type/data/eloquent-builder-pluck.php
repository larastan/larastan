<?php

declare(strict_types=1);

namespace EloquentBuilderPluck;

use App\User;
use Illuminate\Database\Eloquent\Builder;

use function PHPStan\Testing\assertType;

/**
 * @param Builder<User> $users
 */
function test(Builder $users): void {
    assertType('Illuminate\Support\Collection<int, string>', $users->pluck('name'));
    assertType('Illuminate\Support\Collection<string, string>', $users->pluck('name', 'name'));
}
