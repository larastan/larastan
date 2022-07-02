<?php

declare(strict_types=1);

namespace Tests\Features\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model&\Tests\Features\Relations\HasSortingAttribute
 * @extends \Illuminate\Database\Eloquent\Relations\HasMany<TRelatedModel, TDeclaringModel>
 */
class HasSortableMany extends HasMany
{
    /**
     * Set the constraint for a single parent model.
     *
     * @internal Here we exploit that `$this->parent` is defined as
     * `TDeclaringModel` in the base class, otherwise
     * `$this->parent->getSortingCriterion()` and `$this->parent->getSortingDirection()`
     * would be undefined.
     *
     * @return void
     */
    public function addConstraints(): void
    {
        if (static::$constraints) {
            $query = $this->getRelationQuery();

            $query
                ->where($this->foreignKey, '=', $this->getParentKey())
                ->orderBy($this->parent->getSortingCriterion(), $this->parent->getSortingDirection());
        }
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array<TDeclaringModel>  $models
     * @return void
     */
    public function addEagerConstraints(array $models): void
    {
        $query = $this->getRelationQuery();
        foreach ($models as $model) {
            $query->union(
                $query->newQuery()
                    ->where($this->foreignKey, '=', $model->getKey())
                    ->orderBy($model->getSortingCriterion(), $model->getSortingDirection()),
                true
            );
        }
    }
}

interface HasSortingAttribute
{
    public function getSortingCriterion(): string;

    public function getSortingDirection(): string;
}

class Post extends Model
{
}

class Topic extends Model implements HasSortingAttribute
{
    public function getSortingCriterion(): string
    {
        return 'created_at';
    }

    public function getSortingDirection(): string
    {
        return 'asc';
    }

    /**
     * @return HasSortableMany<Post, Topic>
     */
    public function posts(): HasSortableMany
    {
        /** @phpstan-ignore-next-line, see https://github.com/phpstan/phpstan/issues/6732 */
        return new HasSortableMany(Post::query(), $this, 'posts.topic_id', 'id');
    }
}
