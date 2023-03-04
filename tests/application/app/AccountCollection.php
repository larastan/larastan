<?php

namespace App;

use Illuminate\Database\Eloquent\Collection;

/**
 * @template TKey of array-key
 * @template TModel
 * @extends Collection<TKey, TModel>
 */
class AccountCollection extends Collection
{
    /**
     * @return self<TKey, TModel>
     */
    public function filterByActive(): self
    {
        return $this->where('active', true);
    }
}
