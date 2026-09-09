<?php

namespace BuilderOfType;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use function PHPStan\Testing\assertType;

/**
 * @param builder-of<\App\User> $userBuilder
 * @param builder-of<\App\Account> $accountBuilder
 * @param builder-of<\App\Team> $teamBuilder
 * @param builder-of<\App\User|\App\Team> $union
 */
function test($userBuilder, Builder $accountBuilder, Builder $teamBuilder, Builder $union): void
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $userBuilder);
    assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $accountBuilder);
    assertType('App\ChildTeamBuilder', $teamBuilder);
    assertType('App\ChildTeamBuilder|Illuminate\Database\Eloquent\Builder<App\User>', $union);

    assertType('Illuminate\Database\Eloquent\Builder<App\User>', genericMethod(\App\User::class));
    assertType('Illuminate\Database\Eloquent\Builder<App\Account>', genericMethod(\App\Account::class));
    assertType('App\ChildTeamBuilder', genericMethod(\App\Team::class));
}

/**
 * @template T of Model
 *
 * @param class-string<T> $class
 *
 * @return builder-of<T>
 */
function genericMethod(string $class): Builder
{
    return $class::query();
}

class ModelWithStaticBuilder extends Model
{
    /** @return builder-of<static> */
    public function builder(): Builder
    {
        return $this->newQuery();
    }

    /** @return builder-of<$this> */
    public function instanceBuilder(): Builder
    {
        return $this->newEloquentBuilder($this->newBaseQueryBuilder())->setModel($this);
    }

    public function testStaticBuilder(): void
    {
        assertType('Illuminate\Database\Eloquent\Builder<static(BuilderOfType\ModelWithStaticBuilder)>', $this->builder());
        assertType('Illuminate\Database\Eloquent\Builder<$this(BuilderOfType\ModelWithStaticBuilder)>', $this->instanceBuilder());
    }
}

class ChildModelWithStaticBuilder extends ModelWithStaticBuilder {}

function testStaticBuilderOutsideClass(ModelWithStaticBuilder $model, ChildModelWithStaticBuilder $child): void
{
    assertType('Illuminate\Database\Eloquent\Builder<BuilderOfType\ModelWithStaticBuilder>', $model->builder());
    assertType('Illuminate\Database\Eloquent\Builder<BuilderOfType\ChildModelWithStaticBuilder>', $child->builder());
}

/** @template TValue */
class GenericModel extends Model {}

/**
 * @param builder-of<GenericModel<string>> $generic
 * @param builder-of<GenericModel<string>|\App\Team> $union
 * @param builder-of<\App\User&object{extra: string}> $intersection
 * @param builder-of<\App\User>|null $nullable
 */
function testModelTypes($generic, $union, $intersection, $nullable): void
{
    assertType('Illuminate\Database\Eloquent\Builder<BuilderOfType\GenericModel<string>>', $generic);
    assertType('App\ChildTeamBuilder|Illuminate\Database\Eloquent\Builder<BuilderOfType\GenericModel<string>>', $union);
    assertType('Illuminate\Database\Eloquent\Builder<App\User&object{extra: string}>', $intersection);
    assertType('Illuminate\Database\Eloquent\Builder<App\User>|null', $nullable);
}

class PostWithBuilderMethod extends \App\Post
{
    /** @return builder-of<static> */
    public function builder(): Builder
    {
        return $this->newQuery();
    }

    public function testCustomBuilder(): void
    {
        assertType('App\PostBuilder<static(BuilderOfType\PostWithBuilderMethod)>', $this->builder());
    }
}

final class FinalPostWithBuilderMethod extends PostWithBuilderMethod {}

/** @param GenericModel<string> $generic */
function testInheritedAndGenericBuilders(FinalPostWithBuilderMethod $post, GenericModel $generic): void
{
    assertType('App\PostBuilder<BuilderOfType\FinalPostWithBuilderMethod>', $post->builder());
    assertType('Illuminate\Database\Eloquent\Builder<BuilderOfType\GenericModel<string>>', $generic->newQuery());
    assertType('Illuminate\Database\Eloquent\Builder<BuilderOfType\GenericModel<string>>', $generic::query());
}

class ModelWithNeverQuery extends Model
{
    public static function query(): never
    {
        throw new \LogicException();
    }
}

function testNeverQuery(): void
{
    assertType('never', ModelWithNeverQuery::query());
}

/**
 * @param builder-of<\App\User, 'accounts'> $accounts
 * @param builder-of<\App\User, 'posts'> $posts
 * @param builder-of<\App\User, 'posts.comments'> $comments
 * @param builder-of<\App\User, 'posts:id'> $postColumns
 * @param builder-of<\App\User, 'posts.comments:id,post_id'> $commentColumns
 */
function testRelationshipBuilders($accounts, $posts, $comments, $postColumns, $commentColumns): void
{
    assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $accounts);
    assertType('App\PostBuilder<App\Post>', $posts);
    assertType('Illuminate\Database\Eloquent\Builder<App\Comment>', $comments);
    assertType('App\PostBuilder<App\Post>', $postColumns);
    assertType('Illuminate\Database\Eloquent\Builder<App\Comment>', $commentColumns);
}

/**
 * @param builder-of<\App\User, 'posts'|'accounts'> $relations
 * @param builder-of<\App\User, 'posts'|'missing'> $partlyMissing
 * @param builder-of<\App\User, 'posts.comments'|'posts.missing'> $nested
 * @param builder-of<\App\User|\App\Team, 'posts'> $models
 * @param builder-of<\App\User|\App\Team, 'missing'> $missingModels
 * @param builder-of<\App\User&object{extra: string}, 'accounts'> $intersection
 * @param builder-of<\App\User, 'accounts'>|null $nullable
 */
function testRelationshipUnions($relations, $partlyMissing, $nested, $models, $missingModels, $intersection, $nullable): void
{
    assertType('App\PostBuilder<App\Post>|Illuminate\Database\Eloquent\Builder<App\Account>', $relations);
    assertType('App\PostBuilder<App\Post>', $partlyMissing);
    assertType('Illuminate\Database\Eloquent\Builder<App\Comment>', $nested);
    assertType('App\PostBuilder<App\Post>', $models);
    assertType('App\ChildTeamBuilder|Illuminate\Database\Eloquent\Builder<App\User>', $missingModels);
    assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $intersection);
    assertType('Illuminate\Database\Eloquent\Builder<App\Account>|null', $nullable);
}

/**
 * @param builder-of<\App\User, 'missing'> $missing
 * @param builder-of<\App\User, 'posts.missing'> $nested
 * @param builder-of<\App\User, 'missing'|'posts.missing'> $union
 * @param builder-of<\App\User, 'getAllCapsName'> $nonRelation
 * @param builder-of<\App\User, string> $unknown
 * @param builder-of<\App\User, non-empty-string> $nonEmpty
 * @param builder-of<\App\User, ''|'posts.'|'.posts'|'posts..comments'> $malformed
 * @param builder-of<\App\Team, 'missing'> $custom
 */
function testRelationshipFallbacks($missing, $nested, $union, $nonRelation, $unknown, $nonEmpty, $malformed, $custom): void
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $missing);
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $nested);
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $union);
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $nonRelation);
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $unknown);
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $nonEmpty);
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $malformed);
    assertType('App\ChildTeamBuilder', $custom);
}

/**
 * @template TValue
 * @method HasMany<\App\Team, $this> teams()
 */
class ModelWithRelationships extends \App\User
{
    /** @return HasMany<GenericModel<TValue>, $this> */
    public function genericModels(): HasMany
    {
        throw new \LogicException();
    }

    /** @return MorphTo<\App\Post|\App\Team, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return HasMany<\App\Account, $this>|null */
    public function nullableRelation(): ?HasMany
    {
        return null;
    }

    /** @return builder-of<static, 'accounts'> */
    public function accountBuilder(): Builder
    {
        return $this->accounts()->getQuery();
    }

    public function testInsideClass(): void
    {
        assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $this->accountBuilder());
    }
}

/**
 * @param builder-of<ModelWithRelationships<string>, 'genericModels'> $generic
 * @param builder-of<ModelWithRelationships<string>, 'subject'> $polymorphic
 * @param builder-of<ModelWithRelationships<string>, 'subject.comments'> $nestedUnion
 * @param builder-of<ModelWithRelationships<string>, 'teams'> $annotation
 * @param builder-of<ModelWithRelationships<string>, 'nullableRelation'> $nullableRelation
 * @param builder-of<\App\Comment, 'commentable'> $broad
 * @param builder-of<\App\Comment, 'commentable.comments'> $broadNested
 * @param builder-of<\App\User, 'syncableRelation'> $customRelation
 * @param ModelWithRelationships<string> $model
 */
function testRelatedModelTypes($generic, $polymorphic, $nestedUnion, $annotation, $nullableRelation, $broad, $broadNested, $customRelation, $model): void
{
    assertType('Illuminate\Database\Eloquent\Builder<BuilderOfType\GenericModel<string>>', $generic);
    assertType('App\ChildTeamBuilder|App\PostBuilder<App\Post>', $polymorphic);
    assertType('Illuminate\Database\Eloquent\Builder<App\Comment>', $nestedUnion);
    assertType('App\ChildTeamBuilder', $annotation);
    assertType('Illuminate\Database\Eloquent\Builder<BuilderOfType\ModelWithRelationships<string>>', $nullableRelation);
    assertType('Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>', $broad);
    assertType('Illuminate\Database\Eloquent\Builder<App\Comment>', $broadNested);
    assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $customRelation);
    assertType('Illuminate\Database\Eloquent\Builder<App\Account>', $model->accountBuilder());
}

/**
 * @template TModel of Model
 * @template TRelation of string
 * @param class-string<TModel> $class
 * @param TRelation $relation
 * @return builder-of<TModel, TRelation>
 */
function genericRelationshipBuilder(string $class, string $relation): Builder
{
    throw new \LogicException();
}

/**
 * @template TRelation of string
 * @param builder-of<\App\User, TRelation> $builder
 */
function testUnresolvedRelationshipTemplate($builder): void
{
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $builder);
}

/** @param 'posts'|'missing' $relation */
function testRelationshipTemplates(string $relation, string $unknown): void
{
    assertType('App\PostBuilder<App\Post>', genericRelationshipBuilder(\App\User::class, 'posts'));
    assertType('Illuminate\Database\Eloquent\Builder<App\Comment>', genericRelationshipBuilder(\App\User::class, 'posts.comments'));
    assertType('App\PostBuilder<App\Post>', genericRelationshipBuilder(\App\User::class, $relation));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', genericRelationshipBuilder(\App\User::class, 'missing'));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', genericRelationshipBuilder(\App\User::class, $unknown));
}
