<?php

declare(strict_types=1);

namespace EloquentBuilder;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use function PHPStan\Testing\assertType;

User::query()->where(function (Builder $query) {
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query);
});
