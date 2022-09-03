<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;

class Favorites implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @return \App\ValueObjects\Favorites
     */
    public function get($model, $key, $value, $attributes)
    {
        return new \App\ValueObjects\Favorites();
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  Favorites  $value
     */
    public function set($model, $key, $value, $attributes)
    {
        if (! $value instanceof \App\ValueObjects\Favorites) {
            throw new InvalidArgumentException('The given value is not a Favorites instance.');
        }

        return [];
    }
}
