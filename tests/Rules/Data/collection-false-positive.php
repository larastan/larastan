<?php

namespace CollectionFalsePositive;

use Countable;
use Illuminate\Support\Collection;

class Test implements Countable
{
    /** @var Collection<array-key, mixed> */
    protected Collection $collection = new Collection();

    public function count(): int
    {
        return $this->collection->count();
    }
}
