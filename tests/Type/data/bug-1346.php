<?php

namespace Bug1346;

use Illuminate\Database\Eloquent\Builder;

use function PHPStan\Testing\assertType;

/** @template TModel of \Illuminate\Database\Eloquent\Model */
class DailyQuery
{
    /** @var Builder<TModel> */
    private Builder $query;

    /** @return Builder<TModel> */
    public function daily(): Builder
    {
        $query = $this->query->select();
        assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $query);

        return $query;
    }
}

/** @var DailyQuery<\App\User> $query */
assertType('Illuminate\Database\Eloquent\Builder<App\User>', $query->daily());
