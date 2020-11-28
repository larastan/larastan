<?php

namespace App;

use Illuminate\Database\Eloquent\Collection;

/**
 * @template TModel
 * @extends Collection<TModel>
 */
class AccountCollection extends Collection
{
    /**
     * @return self<TModel>
     */
    public function filterByActive(): self
    {
        return $this->where('active', true);
    }
}
