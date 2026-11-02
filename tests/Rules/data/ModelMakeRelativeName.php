<?php

declare(strict_types=1);

namespace Tests\Rules\Data;

use Illuminate\Database\Eloquent\Model;

class ModelMakeRelativeName extends Model
{
    public static function makeStatic(): static
    {
        return static::make();
    }

    public static function makeSelf(): self
    {
        return self::make();
    }
}

class NotAModelMakeRelativeName
{
    public static function make(): self
    {
        return new self();
    }

    public static function makeStatic(): self
    {
        return static::make();
    }
}
