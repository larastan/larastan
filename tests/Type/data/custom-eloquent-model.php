<?php

namespace CustomEloquentModelTests;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use function PHPStan\Testing\assertType;

function testPassthroughEloquentBuilder()
{
    assertType('CustomEloquentModelTests\City|null', City::where('email', 'bar')->first());
}

function testPassthroughQueryBuilder()
{
    assertType('CustomEloquentModelTests\CustomEloquentBuilder<CustomEloquentModelTests\City>', City::whereIntegerInRaw('id', [1, 2, 3]));
}

function testAccessModel()
{
    assertType('Illuminate\Database\Eloquent\Collection<int, CustomEloquentModelTests\City>', City::all()->filter());
}

function testAccessProperty()
{
    assertType('string', City::all()->filter()->first()->name);
}

/**
 * @template TModelClass of CustomEloquentModel

 * @extends \Illuminate\Database\Eloquent\Builder<TModelClass>
 */
class CustomEloquentBuilder extends EloquentBuilder
{

}

/**
 * @mixin CustomEloquentBuilder<static>
 */
class CustomEloquentModel extends Model
{
    /**
     * @param Builder $query
     * @return CustomEloquentBuilder
     */
    public function newEloquentBuilder($query): CustomEloquentBuilder
    {
        return new CustomEloquentBuilder($query);
    }
}

final class City extends CustomEloquentModel
{
    public string $name;
}
