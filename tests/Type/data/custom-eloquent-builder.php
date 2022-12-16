<?php

namespace CustomEloquentBuilder;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use function PHPStan\Testing\assertType;

class CustomEloquentBuilderTest
{
    public function testModelWithCustomBuilderReturnsCorrectModelAfterBuilderMethod()
    {
        assertType('CustomEloquentBuilder\ModelWithCustomBuilder|null', ModelWithCustomBuilder::where('email', 'bar')->first());
    }

    public function testEloquentBuilderMethodReturnsCustomBuilder()
    {
        assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::where('email', 'bar'));
    }

    public function testModelScopeReturnsCustomBuilder(): CustomEloquentBuilder
    {
        assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::foo('foo')->foo('bar'));
    }

    public function testCustomBuilderMethodReturnsBuilder()
    {
        assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::type('foo'));
    }

    public function testCustomBuilderMethodThroughRelation(FooModel $foo)
    {
        assertType('Illuminate\Database\Eloquent\Relations\HasMany<CustomEloquentBuilder\ModelWithCustomBuilder>', $foo->customModels()->category('foo'));
    }

    public function testCustomBuilderMethodAfterDynamicWhere()
    {
        assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::whereEmail(['bar'])->type('foo')->whereEmail(['bar']));
    }

    public function testCustomBuilderMethodWithQueryBuilderMethod()
    {
        assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::whereEmail(['bar'])->categories(['foo'])->whereType(['bar']));
    }

    public function testFindAfterCustomBuilderMethodAfterDynamicWhere()
    {
        assertType('CustomEloquentBuilder\ModelWithCustomBuilder|null', ModelWithCustomBuilder::whereEmail(['bar'])->type('foo')->first());
    }

    public function testFindAfterCustomBuilderMethodAfterDynamicWhereOnExistingVariable()
    {
        assertType('CustomEloquentBuilder\ModelWithCustomBuilder|null', ModelWithCustomBuilder::whereEmail(['bar'])->type('foo')->first());
    }

    public function testCustomBuilderCountMethodThroughRelation(FooModel $foo)
    {
        assertType('int', $foo->customModels()->count());
    }

    public function testCustomBuilderExistsMethodThroughRelation(FooModel $foo)
    {
        assertType('bool', $foo->customModels()->exists());
    }

    public function testWeirdErrorMessage()
    {
        assertType('Illuminate\Database\Eloquent\Collection<int, CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::get());
    }

    public function testFirstOrFailWithCustomBuilder()
    {
        assertType('CustomEloquentBuilder\ModelWithCustomBuilder', ModelWithCustomBuilder::firstOrFail());
    }

    /** @phpstan-return ModelWithCustomBuilder */
    public function testFindOrFailWithCustomBuilder(): ModelWithCustomBuilder
    {
        assertType('CustomEloquentBuilder\ModelWithCustomBuilder', ModelWithCustomBuilder::findOrFail(1));
    }

    public function testFindOrFailWithCustomBuilderWithCollection()
    {
        assertType('Illuminate\Database\Eloquent\Collection<int, CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::findOrFail([1, 2, 3]));
    }

    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterHasBuilderMethod()
    {
        assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->has('users'));
    }

    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterOrHasBuilderMethod()
    {
        assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->orHas('users'));
    }

    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterDoesntHaveBuilderMethod()
    {
        assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->doesntHave('users'));
    }

    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterOrDoesntHaveBuilderMethod()
    {
        assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->orDoesntHave('users'));
    }

    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterWhereHasBuilderMethod()
    {
        assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->whereHas('users'));
    }

    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterOrWhereHasBuilderMethod()
    {
        assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->orWhereHas('users'));
    }

    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterWhereDoesntHaveBuilderMethod()
    {
        assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->whereDoesntHave('users'));
    }

    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterOrWhereDoesntHaveBuilderMethod()
    {
        assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->orWhereDoesntHave('users'));
    }

    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterHasMorphBuilderMethod()
    {
        assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->hasMorph('users', 'types'));
    }

    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterOrHasMorphBuilderMethod()
    {
        assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->orHasMorph('users', 'types'));
    }

    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterDoesntHaveMorphBuilderMethod()
    {
        assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->doesntHaveMorph('users', 'types'));
    }

    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterOrDoesntHaveMorphBuilderMethod()
    {
        assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->orDoesntHaveMorph('users', 'types'));
    }

    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterWhereHasMorphBuilderMethod()
    {
        assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->whereHasMorph('users', 'types'));
    }

    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterOrWhereHasMorphBuilderMethod()
    {
        assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->orWhereHasMorph('users', 'types'));
    }

    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterWhereDoesntHaveMorphBuilderMethod()
    {
        assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->whereDoesntHaveMorph('users', 'types'));
    }

    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterOrWhereDoesntHaveMorphMorphBuilderMethod()
    {
        assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->orWhereDoesntHaveMorph('users', 'types'));
    }

    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterMergeConstraintsFromBuilderMethod()
    {
        assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->mergeConstraintsFrom(ModelWithCustomBuilder::query()));
    }

    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterCustomBuilderMethodRelationChainedWithExplicitQueryMethod()
    {
        assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->whereHas('users')->type('foo'));
    }
}

/**
 * @property string $email
 * @property string $category
 * @property string $type
 */
class ModelWithCustomBuilder extends Model
{
    // Dummy relation
    /** @return HasMany<User> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @param  CustomEloquentBuilder<ModelWithCustomBuilder>  $query
     * @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder>
     */
    public function scopeFoo(CustomEloquentBuilder $query, string $foo): CustomEloquentBuilder
    {
        return $query->where(['email' => $foo]);
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function testCustomBuilderReturnType(): CustomEloquentBuilder
    {
        return $this->where('email', 'bar');
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return CustomEloquentBuilder<ModelWithCustomBuilder>
     */
    public function newEloquentBuilder($query): CustomEloquentBuilder
    {
        return new CustomEloquentBuilder($query);
    }
}

/**
 * @template TModelClass of ModelWithCustomBuilder
 * @extends Builder<ModelWithCustomBuilder>
 */
class CustomEloquentBuilder extends Builder
{
    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function category(string $category): CustomEloquentBuilder
    {
        assertType('static(CustomEloquentBuilder\CustomEloquentBuilder<TModelClass of CustomEloquentBuilder\ModelWithCustomBuilder (class CustomEloquentBuilder\CustomEloquentBuilder, argument)>)', $this->where('category', $category));

        return $this->where('category', $category);
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function type(string $type): CustomEloquentBuilder
    {
        assertType('static(CustomEloquentBuilder\CustomEloquentBuilder<TModelClass of CustomEloquentBuilder\ModelWithCustomBuilder (class CustomEloquentBuilder\CustomEloquentBuilder, argument)>)', $this->where(['type' => $type]));

        return $this->where(['type' => $type]);
    }

    /**
     * @param  string[]  $categories
     * @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder>
     */
    public function categories(array $categories): CustomEloquentBuilder
    {
        assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', $this->whereIn('category', $categories));

        return $this->whereIn('category', $categories);
    }
}

class FooModel extends Model
{
    /** @return HasMany<ModelWithCustomBuilder> */
    public function customModels(): HasMany
    {
        return $this->hasMany(ModelWithCustomBuilder::class);
    }
}

class CustomEloquentBuilderTest1
{
    public function testGetModelFromModelWithCustomBuilderQuery()
    {
        assertType('Illuminate\Database\Eloquent\Collection<int, CustomEloquentBuilder\ModelWithCustomBuilderAndDocBlocks>', ModelWithCustomBuilderAndDocBlocks::query()->get());
    }

    public function testAllModelFromModelWithCustomBuilderQuery()
    {
        assertType('Illuminate\Database\Eloquent\Collection<int, CustomEloquentBuilder\ModelWithCustomBuilderAndDocBlocks>', ModelWithCustomBuilderAndDocBlocks::all());
    }

    public function testQueryModelFromModelWithCustomBuilderQuery()
    {
        assertType('CustomEloquentBuilder\CustomBuilder2<CustomEloquentBuilder\ModelWithCustomBuilderAndDocBlocks>', ModelWithCustomBuilderAndDocBlocks::query());
    }
}

/**
 * @method static CustomBuilder2|ModelWithCustomBuilderAndDocBlocks newModelQuery()
 * @method static CustomBuilder2|ModelWithCustomBuilderAndDocBlocks newQuery()
 * @method static CustomBuilder2|ModelWithCustomBuilderAndDocBlocks query()
 */
class ModelWithCustomBuilderAndDocBlocks extends Model
{
    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return CustomBuilder2<ModelWithCustomBuilderAndDocBlocks>
     */
    public function newEloquentBuilder($query): CustomBuilder2
    {
        return new CustomBuilder2($query);
    }
}

/**
 * @template TModelClass of ModelWithCustomBuilderAndDocBlocks
 * @extends Builder<TModelClass>
 */
class CustomBuilder2 extends Builder
{
}
