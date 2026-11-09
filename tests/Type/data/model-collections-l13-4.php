<?php

namespace ModelCollectionsL134;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\HasCollection;
use Illuminate\Database\Eloquent\Model;
use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('ModelCollectionsL134\BaseCollection<int, ModelCollectionsL134\User>', User::all());
    assertType('ModelCollectionsL134\BaseCollection<int, ModelCollectionsL134\User>', User::query()->where('id', '>', 1)->get());
    assertType('ModelCollectionsL134\BaseCollection<int, ModelCollectionsL134\Admin>', Admin::all());
    assertType('ModelCollectionsL134\TeamCollection', Team::all());
    assertType('ModelCollectionsL134\BaseCollection<int, ModelCollectionsL134\NamedArgumentParent>', NamedArgumentParent::all());
    assertType('ModelCollectionsL134\BaseCollection<int, ModelCollectionsL134\NamedArgumentChild>', NamedArgumentChild::all());
    assertType('ModelCollectionsL134\StringCollection<int, ModelCollectionsL134\StringChild>', StringChild::all());
    assertType('ModelCollectionsL134\StringCollection<int, ModelCollectionsL134\LeadingBackslashString>', LeadingBackslashString::all());
    assertType('ModelCollectionsL134\BaseCollection<int, ModelCollectionsL134\TraitParentChild>', TraitParentChild::all());
    assertType('ModelCollectionsL134\BaseCollection<int, ModelCollectionsL134\TraitChild>', TraitChild::all());
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

/** Laravel only reads a positional argument, so this attribute is skipped. */
#[CollectedBy(collectionClass: TeamCollection::class)]
class NamedArgumentParent extends BaseModel
{

}

class NamedArgumentChild extends NamedArgumentParent
{

}

#[CollectedBy('ModelCollectionsL134\StringCollection')]
class StringParent extends Model
{

}

class StringChild extends StringParent
{

}

#[CollectedBy('\ModelCollectionsL134\StringCollection')]
class LeadingBackslashString extends Model
{

}

#[CollectedBy(BaseCollection::class)]
abstract class TraitParent extends Model
{
    use HasCollection;
}

class TraitParentChild extends TraitParent
{

}

class TraitChild extends BaseModel
{
    use HasCollection;
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

/**
 * @template TKey of array-key
 * @template TModel of Model
 *
 * @extends Collection<TKey, TModel>
 */
class StringCollection extends Collection
{

}

/** @extends Collection<int, Team> */
class TeamCollection extends Collection
{

}
