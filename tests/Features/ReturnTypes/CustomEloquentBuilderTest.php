<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomEloquentBuilderTest
{
    public function testModelWithCustomBuilderReturnsCorrectModelAfterBuilderMethod(): ?ModelWithCustomBuilder
    {
        return ModelWithCustomBuilder::where('email', 'bar')->first();
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function testEloquentBuilderMethodReturnsCustomBuilder(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::query()->where('email', 'bar');
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function testModelScopeReturnsCustomBuilder(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::foo('foo')->foo('bar');
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function testCustomBuilderMethodReturnsBuilder(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::type('foo');
    }

    /** @phpstan-return HasMany<ModelWithCustomBuilder> */
    public function testCustomBuilderMethodThroughRelation(): HasMany
    {
        $foo = new FooModel();

        return $foo->customModels()->category('foo');
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function testCustomBuilderMethodAfterDynamicWhere(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::whereEmail(['bar'])->type('foo')->whereEmail(['bar']);
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function testCustomBuilderMethodWithQueryBuilderMethod(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::whereEmail(['bar'])->categories(['foo'])->whereType(['bar']);
    }

    public function testFindAfterCustomBuilderMethodAfterDynamicWhere(): ?ModelWithCustomBuilder
    {
        return ModelWithCustomBuilder::whereEmail(['bar'])->type('foo')->first();
    }

    public function testFindAfterCustomBuilderMethodAfterDynamicWhereOnExistingVariable(): ?ModelWithCustomBuilder
    {
        $query = ModelWithCustomBuilder::whereEmail(['bar'])->type('foo');

        return $query->first();
    }

    public function testCustomBuilderCountMethodThroughRelation(FooModel $foo): int
    {
        return $foo->customModels()->count();
    }

    public function testCustomBuilderExistsMethodThroughRelation(FooModel $foo): bool
    {
        return $foo->customModels()->exists();
    }

    /**
     * @return Collection<int, ModelWithCustomBuilder>
     */
    public function testWeirdErrorMessage(): Collection
    {
        // Fails even though Builder has correct annotations
        return ModelWithCustomBuilder::get();
    }

    /** @phpstan-return ModelWithCustomBuilder */
    public function testFirstOrFailWithCustomBuilder(): ModelWithCustomBuilder
    {
        return ModelWithCustomBuilder::firstOrFail();
    }

    /** @phpstan-return ModelWithCustomBuilder */
    public function testFindOrFailWithCustomBuilder(): ModelWithCustomBuilder
    {
        return ModelWithCustomBuilder::findOrFail(1);
    }

    /** @phpstan-return Collection<int, ModelWithCustomBuilder> */
    public function testFindOrFailWithCustomBuilderWithCollection(): Collection
    {
        return ModelWithCustomBuilder::findOrFail([1, 2, 3]);
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterHasBuilderMethod(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::query()->has('users');
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterOrHasBuilderMethod(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::query()->orHas('users');
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterDoesntHaveBuilderMethod(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::query()->doesntHave('users');
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterOrDoesntHaveBuilderMethod(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::query()->orDoesntHave('users');
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterWhereHasBuilderMethod(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::query()->whereHas('users');
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterOrWhereHasBuilderMethod(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::query()->orWhereHas('users');
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterWhereDoesntHaveBuilderMethod(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::query()->whereDoesntHave('users');
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterOrWhereDoesntHaveBuilderMethod(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::query()->orWhereDoesntHave('users');
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterHasMorphBuilderMethod(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::query()->hasMorph('users', 'types');
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterOrHasMorphBuilderMethod(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::query()->orHasMorph('users', 'types');
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterDoesntHaveMorphBuilderMethod(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::query()->doesntHaveMorph('users', 'types');
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterOrDoesntHaveMorphBuilderMethod(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::query()->orDoesntHaveMorph('users', 'types');
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterWhereHasMorphBuilderMethod(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::query()->whereHasMorph('users', 'types');
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterOrWhereHasMorphBuilderMethod(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::query()->orWhereHasMorph('users', 'types');
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterWhereDoesntHaveMorphBuilderMethod(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::query()->whereDoesntHaveMorph('users', 'types');
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterOrWhereDoesntHaveMorphMorphBuilderMethod(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::query()->orWhereDoesntHaveMorph('users', 'types');
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterMergeConstraintsFromBuilderMethod(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::query()->mergeConstraintsFrom(ModelWithCustomBuilder::query());
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
    public function testModelWithCustomBuilderReturnsCustomEloquentBuilderAfterCustomBuilderMethodRelationChainedWithExplicitQueryMethod(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::query()->whereHas('users')->type('foo');
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
    /**
     * @param  string  $category
     * @return CustomEloquentBuilder
     * @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder>
     */
    public function category(string $category): CustomEloquentBuilder
    {
        return $this->where('category', $category);
    }

    /**
     * @param  string  $type
     * @return CustomEloquentBuilder
     * @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder>
     */
    public function type(string $type): CustomEloquentBuilder
    {
        return $this->where(['type' => $type]);
    }

    /**
     * @param  string[]  $categories
     * @return CustomEloquentBuilder
     * @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder>
     */
    public function categories(array $categories): CustomEloquentBuilder
    {
        return $this->whereIn('category', $categories);
    }
}

class CustomEloquentBuilderTest1
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \Tests\Features\ReturnTypes\ModelWithCustomBuilderAndDocBlocks>
     */
    public function testGetModelFromModelWithCustomBuilderQuery()
    {
        return ModelWithCustomBuilderAndDocBlocks::query()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \Tests\Features\ReturnTypes\ModelWithCustomBuilderAndDocBlocks>
     */
    public function testAllModelFromModelWithCustomBuilderQuery()
    {
        return ModelWithCustomBuilderAndDocBlocks::all();
    }

    /**
     * @return CustomBuilder2<\Tests\Features\ReturnTypes\ModelWithCustomBuilderAndDocBlocks>
     */
    public function testQueryModelFromModelWithCustomBuilderQuery()
    {
        return ModelWithCustomBuilderAndDocBlocks::query();
    }
}

/**
 * @method static \Tests\Features\ReturnTypes\CustomBuilder2|\Tests\Features\ReturnTypes\ModelWithCustomBuilderAndDocBlocks newModelQuery()
 * @method static \Tests\Features\ReturnTypes\CustomBuilder2|\Tests\Features\ReturnTypes\ModelWithCustomBuilderAndDocBlocks newQuery()
 * @method static \Tests\Features\ReturnTypes\CustomBuilder2|\Tests\Features\ReturnTypes\ModelWithCustomBuilderAndDocBlocks query()
 */
class ModelWithCustomBuilderAndDocBlocks extends Model
{
    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return CustomBuilder2<\Tests\Features\ReturnTypes\ModelWithCustomBuilderAndDocBlocks>
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
