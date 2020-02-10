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

    public function testEloquentBuilderMethodReturnsCustomBuilder(): CustomBuilder
    {
        return ModelWithCustomBuilder::with('foo')->where('foo', 'bar');
    }

    public function testModelScopeReturnsCustomBuilder(): CustomBuilder
    {
        return ModelWithCustomBuilder::foo('foo')->foo('bar');
    }

    public function testCustomBuilderMethodReturnsBuilder(): CustomBuilder
    {
        return ModelWithCustomBuilder::type('foo');
    }

    public function testCustomBuilderMethodThroughRelation(): HasMany
    {
        $foo = new FooModel();

        return $foo->customModels()->category('foo');
    }

    public function testCustomBuilderMethodAfterDynamicWhere(): CustomBuilder
    {
        return ModelWithCustomBuilder::whereFoo(['bar'])->type('foo')->whereFoo(['bar']);
    }

    public function testCustomBuilderMethodWithQueryBuilderMethod(): CustomBuilder
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
    public function scopeFoo(CustomBuilder $query, string $foo): CustomBuilder
    {
        return $query->where(['foo' => $foo]);
    }

    public function testCustomBuilderReturnType(): CustomBuilder
    {
        return $this->where('foo', 'bar');
    }

    /**
     * @param \Illuminate\Database\Query\Builder $query
     *
     * @return CustomBuilder<ModelWithCustomBuilder>
     */
    public function newEloquentBuilder($query): CustomBuilder
    {
        return new CustomBuilder($query);
    }
}

/**
 * @template TModelClass
 * @extends Builder<TModelClass>
 */
class CustomBuilder extends Builder
{
    /**
     * @param string $category
     *
     * @return CustomBuilder
     * @phpstan-return CustomBuilder<TModelClass>
     */
    public function category(string $category): CustomBuilder
    {
        return $this->where('category', $category);
    }

    /**
     * @param string $type
     *
     * @return CustomBuilder
     * @phpstan-return CustomBuilder<TModelClass>
     */
    public function type(string $type): CustomBuilder
    {
        return $this->where(['type' => $type]);
    }

    /**
     * @param string[] $categories
     *
     * @return CustomBuilder
     * @phpstan-return CustomBuilder<TModelClass>
     */
    public function categories(array $categories): CustomBuilder
    {
        return $this->whereIn('category', $categories);
    }
}
