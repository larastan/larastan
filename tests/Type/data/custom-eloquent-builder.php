<?php

namespace CustomEloquentBuilder;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\SoftDeletes;
use function PHPStan\Testing\assertType;

function doFoo(FooModel $foo, NonGenericBuilder $nonGenericBuilder): void
{
    assertType('CustomEloquentBuilder\ModelWithCustomBuilder|null', ModelWithCustomBuilder::where('email', 'bar')->first());
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::where('email', 'bar'));
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::foo('foo'));
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::foo('foo')->foo('bar'));
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::type('foo'));
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<CustomEloquentBuilder\ModelWithCustomBuilder>', $foo->customModels()->category('foo'));
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::whereEmail(['bar'])->type('foo')->whereEmail(['bar']));
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::whereEmail(['bar'])->categories(['foo'])->whereType(['bar']));
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::withTrashed());
    assertType('CustomEloquentBuilder\ModelWithCustomBuilder|null', ModelWithCustomBuilder::whereEmail(['bar'])->type('foo')->first());
    assertType('CustomEloquentBuilder\ModelWithCustomBuilder|null', ModelWithCustomBuilder::whereEmail(['bar'])->type('foo')->first());
    assertType('int', $foo->customModels()->count());
    assertType('bool', $foo->customModels()->exists());
    assertType('Illuminate\Database\Eloquent\Collection<int, CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::get());
    assertType('CustomEloquentBuilder\ModelWithCustomBuilder', ModelWithCustomBuilder::firstOrFail());
    assertType('CustomEloquentBuilder\ModelWithCustomBuilder', ModelWithCustomBuilder::findOrFail(1));
    assertType('Illuminate\Database\Eloquent\Collection<int, CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::findOrFail([1, 2, 3]));
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->has('users'));
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->orHas('users'));
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->doesntHave('users'));
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->orDoesntHave('users'));
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->whereHas('users'));
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->withWhereHas('users'));
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->orWhereHas('users'));
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->whereDoesntHave('users'));
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->orWhereDoesntHave('users'));
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->hasMorph('users', 'types'));
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->orHasMorph('users', 'types'));
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->doesntHaveMorph('users', 'types'));
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->orDoesntHaveMorph('users', 'types'));
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->whereHasMorph('users', 'types'));
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->orWhereHasMorph('users', 'types'));
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->whereDoesntHaveMorph('users', 'types'));
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->orWhereDoesntHaveMorph('users', 'types'));
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->mergeConstraintsFrom(ModelWithCustomBuilder::query()));
    assertType('CustomEloquentBuilder\CustomEloquentBuilder<CustomEloquentBuilder\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->whereHas('users')->type('foo'));
    assertType('Illuminate\Database\Eloquent\Collection<int, CustomEloquentBuilder\ModelWithCustomBuilderAndDocBlocks>', ModelWithCustomBuilderAndDocBlocks::query()->get());
    assertType('Illuminate\Database\Eloquent\Collection<int, CustomEloquentBuilder\ModelWithCustomBuilderAndDocBlocks>', ModelWithCustomBuilderAndDocBlocks::all());
    assertType('CustomEloquentBuilder\CustomBuilder2<CustomEloquentBuilder\ModelWithCustomBuilderAndDocBlocks>', ModelWithCustomBuilderAndDocBlocks::query());
    assertType('CustomEloquentBuilder\NonGenericBuilder', $nonGenericBuilder->skip(5));
    assertType('CustomEloquentBuilder\NonGenericBuilder', $nonGenericBuilder->category('foo'));
}

/**
 * @property string $email
 * @property string $category
 * @property string $type
 */
class ModelWithCustomBuilder extends Model
{
    use SoftDeletes;

    // Dummy relation
    /** @return HasMany<User> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @param  CustomEloquentBuilder<ModelWithCustomBuilder>  $query
     *
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
 *
 * @extends Builder<ModelWithCustomBuilder>
 */
class CustomEloquentBuilder extends Builder
{
    /** @return $this */
    public function category(string $category): static
    {
        return $this->where('category', $category);
    }

    /** @return $this */
    public function type(string $type): static
    {
        return $this->where(['type' => $type]);
    }

    /**
     * @param  string[]  $categories
     *
     * @return $this
     */
    public function categories(array $categories): static
    {
        return $this->whereIn('category', $categories);
    }

    private function test(): void
    {
        $thisType = '$this(CustomEloquentBuilder\CustomEloquentBuilder<TModelClass of CustomEloquentBuilder\ModelWithCustomBuilder (class CustomEloquentBuilder\CustomEloquentBuilder, argument)>)';
        assertType($thisType, $this->where('email', 'bar'));
        assertType($thisType, $this->where(['email' => 'bar']));
        assertType($thisType, $this->whereNull('finished_at'));
        assertType($thisType, $this->whereNotNull('finished_at'));
        assertType($thisType, $this->orderBy('name'));
        assertType($thisType, $this->orderByDesc('name'));
        assertType($thisType, $this->whereRaw('lower(email) = foo'));
        assertType($thisType, $this->whereRelation('user', 'id', 1));
        assertType($thisType, $this->join('user', 'user.id', '=', 'id'));
        assertType($thisType, $this->leftJoin('user', 'user.id', '=', 'id'));
        assertType($thisType, $this->rightJoin('user', 'user.id', '=', 'id'));
        assertType($thisType, $this->select('*'));
        assertType($thisType, $this->selectRaw('count(*) as count'));
        assertType($thisType, $this->withTrashed());
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
 *
 * @extends Builder<TModelClass>
 */
class CustomBuilder2 extends Builder
{
}

class ModelWithNonGenericBuilder extends ModelWithCustomBuilder
{
    use SoftDeletes;

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return NonGenericBuilder
     */
    public function newEloquentBuilder($query): NonGenericBuilder
    {
        return new NonGenericBuilder($query);
    }
}

/** @extends Builder<ModelWithNonGenericBuilder> */
class NonGenericBuilder extends CustomEloquentBuilder
{
    private function test(): void
    {
        $thisType = '$this(CustomEloquentBuilder\NonGenericBuilder)';
        assertType($thisType, $this->whereNull('finished_at'));
        assertType($thisType, $this->whereNotNull('finished_at'));
        assertType($thisType, $this->orderBy('name'));
        assertType($thisType, $this->orderByDesc('name'));
        assertType($thisType, $this->whereRaw('lower(email) = foo'));
        assertType($thisType, $this->whereRelation('user', 'id', 1));
        assertType($thisType, $this->join('user', 'user.id', '=', 'id'));
        assertType($thisType, $this->leftJoin('user', 'user.id', '=', 'id'));
        assertType($thisType, $this->rightJoin('user', 'user.id', '=', 'id'));
        assertType($thisType, $this->select('*'));
        assertType($thisType, $this->selectRaw('count(*) as count'));
        assertType($thisType, $this->withTrashed());
    }
}
