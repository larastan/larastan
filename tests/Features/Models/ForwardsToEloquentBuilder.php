<?php

declare(strict_types=1);

namespace Tests\Features\Models;

use App\User;
use Illuminate\Database\Eloquent\Builder;

class ForwardsToEloquentBuilder
{
    public function testForwardsToEloquentBuilder(): Builder
    {
        return (new User)->withGlobalScope('test', function () {
        });
    }
}
