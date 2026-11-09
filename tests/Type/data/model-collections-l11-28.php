<?php

namespace ModelCollectionsL1128;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('ModelCollectionsL1128\UserCollection', User::all());
    assertType('ModelCollectionsL1128\UserCollection', User::query()->paginate()->getCollection());
    assertType('ModelCollectionsL1128\UserCollection', User::query()->simplePaginate()->getCollection());
    assertType('ModelCollectionsL1128\UserCollection', User::query()->cursorPaginate()->getCollection());
    assertType('ModelCollectionsL1128\UserCollection', User::query()->where('id', '>', 1)->get());
    assertType('ModelCollectionsL1128\TeamCollection', Team::all());
    assertType('ModelCollectionsL1128\PostCollection', Post::all());
}

#[CollectedBy(UserCollection::class)]
class User extends Model
{

}

#[CollectedBy(UserCollection::class)]
class Team extends Model
{
    /** @param array<int, static> $models */
    public function newCollection(array $models = []): TeamCollection
    {
        return new TeamCollection($models);
    }
}

#[CollectedBy('ModelCollectionsL1128\PostCollection')]
class Post extends Model
{

}

/** @extends Collection<int, User> */
class UserCollection extends Collection
{

}

/** @extends Collection<int, Team> */
class TeamCollection extends Collection
{

}

/** @extends Collection<int, Post> */
class PostCollection extends Collection
{

}
