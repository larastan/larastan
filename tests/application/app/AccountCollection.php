<?php

namespace App;

use Illuminate\Database\Eloquent\Collection;

/**
 * @template TKey of array-key
 * @template TValue
 * @extends Collection<TKey, TValue>
 */
class AccountCollection extends Collection
{
    /**
     * @return self<TKey, TValue>
     */
    public function filterByActive(): self
    {
        return $this->where('active', true);
    }
}
