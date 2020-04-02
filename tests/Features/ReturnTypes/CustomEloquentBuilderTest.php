<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomEloquentBuilderTest
{
    public function testModelWithCustomBuilderReturnsCorrectModelAfterBuilderMethod(): ?ModelWithCustomBuilder
    {
        return ModelWithCustomBuilder::where('foo', 'bar')->first();
    }

    public function testEloquentBuilderMethodReturnsCustomBuilder(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::with('foo')->where('foo', 'bar');
    }

    public function testModelScopeReturnsCustomBuilder(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::foo('foo')->foo('bar');
    }

    public function testCustomBuilderMethodReturnsBuilder(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::type('foo');
    }

    public function testCustomBuilderMethodThroughRelation(): HasMany
    {
        $foo = new FooModel();

        return $foo->customModels()->category('foo');
    }

    public function testCustomBuilderMethodAfterDynamicWhere(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::whereFoo(['bar'])->type('foo')->whereFoo(['bar']);
    }

    public function testCustomBuilderMethodWithQueryBuilderMethod(): CustomEloquentBuilder
    {
        return ModelWithCustomBuilder::whereFoo(['bar'])->categories(['foo'])->whereFoo(['bar']);
    }

    public function testFindAfterCustomBuilderMethodAfterDynamicWhere(): ?ModelWithCustomBuilder
    {
        return ModelWithCustomBuilder::whereFoo(['bar'])->type('foo')->first();
    }

    public function testFindAfterCustomBuilderMethodAfterDynamicWhereOnExistingVariable(): ?ModelWithCustomBuilder
    {
        $query = ModelWithCustomBuilder::whereFoo(['bar'])->type('foo');

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
}

class FooModel extends Model
{
    public function customModels(): HasMany
    {
        return $this->hasMany(ModelWithCustomBuilder::class);
    }
}

class ModelWithCustomBuilder extends Model
{
    public function scopeFoo(CustomEloquentBuilder $query, string $foo): CustomEloquentBuilder
    {
        return $query->where(['foo' => $foo]);
    }

    public function testCustomBuilderReturnType(): CustomEloquentBuilder
    {
        return $this->where('foo', 'bar');
    }

    /**
     * @param \Illuminate\Database\Query\Builder $query
     *
     * @return CustomEloquentBuilder<ModelWithCustomBuilder>
     */
    public function newEloquentBuilder($query): CustomEloquentBuilder
    {
        return new CustomEloquentBuilder($query);
    }
}

/**
 * @template TModelClass
 * @extends Builder<TModelClass>
 */
class CustomEloquentBuilder extends Builder
{
    /**
     * @param string $category
     *
     * @return CustomEloquentBuilder
     * @phpstan-return CustomEloquentBuilder<TModelClass>
     */
    public function category(string $category): CustomEloquentBuilder
    {
        return $this->where('category', $category);
    }

    /**
     * @param string $type
     *
     * @return CustomEloquentBuilder
     * @phpstan-return CustomEloquentBuilder<TModelClass>
     */
    public function type(string $type): CustomEloquentBuilder
    {
        return $this->where(['type' => $type]);
    }

    /**
     * @param string[] $categories
     *
     * @return CustomEloquentBuilder
     * @phpstan-return CustomEloquentBuilder<TModelClass>
     */
    public function categories(array $categories): CustomEloquentBuilder
    {
        return $this->whereIn('category', $categories);
    }
}

class CustomEloquentBuilderTest1
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection<\Tests\Features\ReturnTypes\ModelWithCustomBuilderAndDocBlocks>
     */
    public function testGetModelFromModelWithCustomBuilderQuery()
    {
        return ModelWithCustomBuilderAndDocBlocks::query()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<\Tests\Features\ReturnTypes\ModelWithCustomBuilderAndDocBlocks>
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
     * @param \Illuminate\Database\Query\Builder $query
     *
     * @return CustomBuilder2<\Tests\Features\ReturnTypes\ModelWithCustomBuilderAndDocBlocks>
     */
    public function newEloquentBuilder($query): CustomBuilder2
    {
        return new CustomBuilder2($query);
    }
}

/**
 * @template TModelClass
 * @extends Builder<TModelClass>
 */
class CustomBuilder2 extends Builder
{
}
