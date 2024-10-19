<?php

namespace Bug1985;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('Bug1985\ModelCollection<int, Bug1985\User>', User::all()->unique());
}

class User extends Model
{
    /** @return ModelCollection<int, User> */
    public function newCollection(array $models = []): ModelCollection
    {
        return new ModelCollection($models);
    }
}

/**
 * @template TKey of array-key
 * @template TModel of Model
 *
 * @extends Collection<TKey, TModel>
 */
class ModelCollection extends Collection
{
}
