<?php

declare(strict_types=1);

namespace Tests\Rules\Data;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    /** @var array<int, string> */
    protected $appends = [
        'computed_property',
        'non_existent',
        'email', // This is a database column, should not be appended
        'name', // This is an accessor, should not be appended
    ];

    protected function computedProperty(): Attribute
    {
        return Attribute::get(fn () => 'foo');
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn ($value) => ucwords($value));
    }
}
