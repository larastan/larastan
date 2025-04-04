<?php

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, int>
 */
class Foo implements Arrayable
{
    public function toArray()
    {

    }
}
