<?php

namespace Bug2073;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use function PHPStan\Testing\assertType;

/**
 * @param Collection<int, User<int>> $collection
 *
 * @return void
 */
function test(Collection $collection): void
{
    assertType('Bug2073\ModelCollection<int, Bug2073\User<int>>', ModelCollection::make($collection));
    assertType('Bug2073\ModelCollection<int, Bug2073\User<int>>', ModelCollection::make($collection->all()));
}

/**
 * @template TKey of array-key
 * @template TModel of Model
 *
 * @extends EloquentCollection<TKey, TModel>
 */
class ModelCollection extends EloquentCollection {}

/**
 * @template T
 */
class User extends Model
{
    /**
     * @param User[] $models
     * @return ModelCollection<int, User>
     */
    public function newCollection(array $models = []): ModelCollection
    {
        return new ModelCollection($models);
    }
}
