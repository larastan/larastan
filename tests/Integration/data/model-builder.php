<?php

namespace ModelBuilder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    /** @return Builder<static> */
    public static function testQueryStatic(): Builder
    {
        return static::query();
    }

    public static function testCreateStatic(): static
    {
        return static::query()->create();
    }

    public static function testCreateSelf(): static
    {
        return self::query()->create();
    }
}
