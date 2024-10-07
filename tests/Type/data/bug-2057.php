<?php

namespace Bug2057;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

use function PHPStan\Testing\assertType;

/** @template T of \Illuminate\Database\Eloquent\Model */
interface TreeLikeCollection
{
    /** @return $this */
    public function getTree(): static;
}

/**
 * @template TKey of array-key
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends Collection<TKey, TModel>
 * @implements TreeLikeCollection<TModel>
 */
class TreeCollection extends Collection implements TreeLikeCollection
{
    /** @return $this */
    public function getTree(): static
    {
        return $this;
    }
}

class User extends Model
{
    /**
     * @param  array<array-key, static>  $models
     * @return Collection<int, static>&TreeLikeCollection<static>
     */
    public function newCollection(array $models = []): Collection&TreeLikeCollection
    {
        /** @phpstan-ignore return.type (generics) */
        return new TreeCollection($models);
    }
}

class Post extends Model
{
    /**
     * @param  array<array-key, static>  $models
     * @return TreeCollection<int, static>
     */
    public function newCollection(array $models = []): Collection&TreeLikeCollection
    {
        /** @phpstan-ignore return.type (generics) */
        return new TreeCollection($models);
    }
}

function test(): void
{
    assertType('Bug2057\TreeLikeCollection<static(Bug2057\User)>&Illuminate\Database\Eloquent\Collection<int, Bug2057\User>', User::all());
    assertType('Bug2057\TreeLikeCollection<static(Bug2057\User)>&Illuminate\Database\Eloquent\Collection<int, Bug2057\User>', User::all()->getTree());
    assertType('Bug2057\TreeCollection<int, Bug2057\Post>', Post::all());
    assertType('Bug2057\TreeCollection<int, Bug2057\Post>', Post::all()->getTree());
}
