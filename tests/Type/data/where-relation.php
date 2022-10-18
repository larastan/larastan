<?php

namespace WhereRelation;

use Illuminate\Database\Eloquent\Model;
use function PHPStan\Testing\assertType;

class Field extends Model
{
}

class WhereRelationTest
{
    private function getFieldsFromOrder(): void
    {
        $query = Field::query();
        assertType('Illuminate\Database\Eloquent\Builder<WhereRelation\Field>', $query);

        $query = $query->whereRelation('orderTypes', 'order_types.id', '=', 1);
        assertType('Illuminate\Database\Eloquent\Builder<WhereRelation\Field>', $query);

        $collection = $query->get();
        assertType('Illuminate\Database\Eloquent\Collection<int, WhereRelation\Field>', $collection);
    }
}
