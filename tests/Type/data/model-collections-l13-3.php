<?php

namespace ModelCollectionsL133;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('ModelCollectionsL133\BaseCollection<int, ModelCollectionsL133\User>', User::all());
    assertType('ModelCollectionsL133\BaseCollection<int, ModelCollectionsL133\User>', User::query()->where('id', '>', 1)->get());
    assertType('ModelCollectionsL133\BaseCollection<int, ModelCollectionsL133\Admin>', Admin::all());
    assertType('ModelCollectionsL133\TeamCollection', Team::all());
}

#[CollectedBy(BaseCollection::class)]
abstract class BaseModel extends Model
{

}

class User extends BaseModel
{

}

class Admin extends User
{

}

class Team extends BaseModel
{
    /** @param array<int, static> $models */
    public function newCollection(array $models = []): TeamCollection
    {
        return new TeamCollection($models);
    }
}

/**
 * @template TKey of array-key
 * @template TModel of Model
 *
 * @extends Collection<TKey, TModel>
 */
class BaseCollection extends Collection
{

}

/** @extends Collection<int, Team> */
class TeamCollection extends Collection
{

}
