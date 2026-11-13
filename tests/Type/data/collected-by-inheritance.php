<?php

namespace CollectedByInheritance;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\HasCollection;
use Illuminate\Database\Eloquent\Model;
use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('CollectedByInheritance\BaseCollection<int, CollectedByInheritance\User>', User::all());
    assertType('CollectedByInheritance\BaseCollection<int, CollectedByInheritance\User>', User::query()->where('id', '>', 1)->get());
    assertType('CollectedByInheritance\BaseCollection<int, CollectedByInheritance\Admin>', Admin::all());
    assertType('CollectedByInheritance\TeamCollection', Team::all());
    assertType('CollectedByInheritance\BaseCollection<int, CollectedByInheritance\NamedArgumentParent>', NamedArgumentParent::all());
    assertType('CollectedByInheritance\BaseCollection<int, CollectedByInheritance\NamedArgumentChild>', NamedArgumentChild::all());
    assertType('CollectedByInheritance\StringCollection<int, CollectedByInheritance\StringChild>', StringChild::all());
    assertType('CollectedByInheritance\StringCollection<int, CollectedByInheritance\LeadingBackslashString>', LeadingBackslashString::all());
    assertType('CollectedByInheritance\BaseCollection<int, CollectedByInheritance\TraitParentChild>', TraitParentChild::all());
    assertType('CollectedByInheritance\BaseCollection<int, CollectedByInheritance\TraitChild>', TraitChild::all());
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

#[CollectedBy('CollectedByInheritance\StringCollection')]
class StringParent extends Model
{

}

class StringChild extends StringParent
{

}

#[CollectedBy('\CollectedByInheritance\StringCollection')]
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
