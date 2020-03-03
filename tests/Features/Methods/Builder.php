<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use App\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class Builder
{
    public function testGroupBy(): EloquentBuilder
    {
        return User::query()->groupBy('foo', 'bar');
    }

    public function testDynamicWhereAsString(): ?User
    {
        return (new User())->whereFoo('bar')->first();
    }

    public function testDynamicWhereMultiple(): ?User
    {
        return User::whereIdAndEmail(1, 'foo@example.com')->first();
    }

    public function testDynamicWhereAsInt(): ?User
    {
        return (new User())->whereFoo(1)->first();
    }
}
