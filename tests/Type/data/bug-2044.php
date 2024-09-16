<?php

namespace Bug2044;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

use function PHPStan\Testing\assertType;

/** @template TModel of \Illuminate\Database\Eloquent\Model */
class Repository
{
    /** @var Builder<TModel> */
    private Builder $query;

    /** @return Collection<int, TModel> */
    public function all(): Collection
    {
        $models = $this->query->get();
        assertType('Illuminate\Database\Eloquent\Collection<int, TModel of Illuminate\Database\Eloquent\Model (class Bug2044\Repository, argument)>', $models);

        return $models;
    }
}
