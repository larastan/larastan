<?php

namespace App;

use Illuminate\Database\Eloquent\Collection;

/**
 * @template TKey
 * @template TModel
 *
 * @extends Collection<TKey, TModel>
 */
class AccountCollection extends Collection
{
    /**
     * @return self<int, TModel>
     */
    public function filterByActive(): self
    {
        return $this->where('active', true);
    }
}
