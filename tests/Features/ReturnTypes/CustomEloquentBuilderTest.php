<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

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
        return ModelWithCustomBuilder::with('foo')->where('email', 'bar');
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
        return ModelWithCustomBuilder::whereFoo(['bar'])->type('foo')->whereFoo(['bar']);
    }

    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
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

    /**
     * @return Collection<ModelWithCustomBuilder>
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

    /** @phpstan-return Collection<ModelWithCustomBuilder> */
    public function testFindOrFailWithCustomBuilderWithCollection(): Collection
    {
        return ModelWithCustomBuilder::findOrFail([1, 2, 3]);
    }
}

class FooModel extends Model
{
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
    /** @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder> */
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
 * @template TModelClass of ModelWithCustomBuilder
 * @extends Builder<ModelWithCustomBuilder>
 */
class CustomEloquentBuilder extends Builder
{
    /**
     * @param string $category
     *
     * @return CustomEloquentBuilder
     * @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder>
     */
    public function category(string $category): CustomEloquentBuilder
    {
        return $this->where('category', $category);
    }

    /**
     * @param string $type
     *
     * @return CustomEloquentBuilder
     * @phpstan-return CustomEloquentBuilder<ModelWithCustomBuilder>
     */
    public function type(string $type): CustomEloquentBuilder
    {
        return $this->where(['type' => $type]);
    }

    /**
     * @param string[] $categories
     *
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
 * @template TModelClass of ModelWithCustomBuilderAndDocBlocks
 * @extends Builder<TModelClass>
 */
class CustomBuilder2 extends Builder
{
}

abstract class AbstractQueryBuilder extends Builder
{
}

class QueryBuilderWithoutDocBlock extends AbstractQueryBuilder
{
    public function whereFooBar(): QueryBuilderWithoutDocBlock
    {
        return $this
            ->leftJoin('foo', 'bar.id', '=', 'foo.bar')
            ->whereNull('bar.baz')
            ->orWhereNull('foo.bar')
            ->orWhereNotNull('foo.biz');
    }
}
