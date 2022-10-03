<?php

namespace Bug1346;

use Illuminate\Database\Eloquent\Builder;
use function PHPStan\Testing\assertType;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
class DailyQuery
{
    /**
     * @var Builder<TModel>
     */
    private Builder $query;

    public function daily()
    {
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $this->query->select());
    }
}
