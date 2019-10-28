<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use App\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class Builder
{
    public function testGroupBy() : EloquentBuilder
    {
        return User::query()->groupBy('foo', 'bar');
    }
}
