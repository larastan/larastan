<?php

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<int, Arrayable<string, int>>
 */
class FooList implements Arrayable
{
    public function toArray()
    {

    }
}
