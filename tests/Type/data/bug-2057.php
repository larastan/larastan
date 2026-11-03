<?php

namespace Bug2057;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use function PHPStan\Testing\assertType;

/** @template TModel of Model */
interface TreeLikeCollection
{
}

/**
 * @template TKey of array-key
 * @template TModel of Model
 *
 * @extends Collection<TKey, TModel>
 * @implements TreeLikeCollection<TModel>
 */
class TreeCollection extends Collection implements TreeLikeCollection
{
}

/**
 * @template TKey of array-key
 * @template TModel of Model
 *
 * @extends Collection<TKey, TModel>
 */
class FlatCollection extends Collection
{
}

/**
 * @template TNode of Model
 * @template TLabel of \Stringable
 */
interface Labelled
{
}

/**
 * @template TKey of array-key
 * @template TModel of Model
 *
 * @extends Collection<TKey, TModel>
 * @implements Labelled<TModel, \Stringable>
 */
class LabelledCollection extends Collection implements Labelled
{
}

class Category extends Model
{
    /**
     * @param  array<int, static>  $models
     * @return Collection<int, static>&TreeLikeCollection<static>
     */
    public function newCollection(array $models = []): Collection&TreeLikeCollection
    {
        return new TreeCollection($models);
    }

    /** @return HasMany<Category, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
}

class Tag extends Model
{
    /**
     * @param  array<int, static>  $models
     * @return TreeCollection<int, static>|FlatCollection<int, static>
     */
    public function newCollection(array $models = []): TreeCollection|FlatCollection
    {
        return new FlatCollection($models);
    }
}

class Node extends Model
{
    /**
     * @param  array<int, static>  $models
     * @return Collection<int, static>&Labelled<static, \Stringable>
     */
    public function newCollection(array $models = []): Collection&Labelled
    {
        return new LabelledCollection($models);
    }
}

function test(Category $category): void
{
    assertType('Bug2057\TreeLikeCollection<Bug2057\Category>&Illuminate\Database\Eloquent\Collection<int, Bug2057\Category>', Category::all());
    assertType('Bug2057\TreeLikeCollection<Bug2057\Category>&Illuminate\Database\Eloquent\Collection<int, Bug2057\Category>', Category::query()->get());
    assertType('Bug2057\TreeLikeCollection<Bug2057\Category>&Illuminate\Database\Eloquent\Collection<int, Bug2057\Category>', $category->children()->get());
    assertType('Bug2057\TreeLikeCollection<Bug2057\Category>&Illuminate\Database\Eloquent\Collection<int, Bug2057\Category>', $category->children);

    assertType('Bug2057\FlatCollection<int, Bug2057\Tag>|Bug2057\TreeCollection<int, Bug2057\Tag>', Tag::all());
    assertType('Bug2057\FlatCollection<int, Bug2057\Tag>|Bug2057\TreeCollection<int, Bug2057\Tag>', Tag::query()->get());

    assertType('Bug2057\Labelled<Bug2057\Node, Stringable>&Illuminate\Database\Eloquent\Collection<int, Bug2057\Node>', Node::all());
    assertType('Bug2057\Labelled<Bug2057\Node, Stringable>&Illuminate\Database\Eloquent\Collection<int, Bug2057\Node>', Node::query()->get());
}
