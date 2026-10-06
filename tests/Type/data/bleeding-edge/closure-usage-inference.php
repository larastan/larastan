<?php

namespace BleedingEdgeClosureUsageInference;

use App\User;

use function PHPStan\Testing\assertType;

function relationCallbacks(): void
{
    $constrain = function ($query): void {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $query);
    };
    User::query()->whereHas('accounts', $constrain);

    $each = function ($user): void {
        assertType('App\User', $user);
    };
    User::all()->each($each);
}
