<?php

namespace BuilderOfType;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
