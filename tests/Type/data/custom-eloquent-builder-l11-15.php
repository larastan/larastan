<?php

namespace CustomEloquentBuilderL11;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

use function PHPStan\Testing\assertType;

function test(FooModel $foo, NonGenericBuilder $nonGenericBuilder): void
{
    assertType('CustomEloquentBuilderL11\ModelWithCustomBuilder|null', ModelWithCustomBuilder::where('email', 'bar')->first());
    assertType('CustomEloquentBuilderL11\CustomEloquentBuilder<CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::where('email', 'bar'));
    assertType('CustomEloquentBuilderL11\CustomEloquentBuilder<CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::foo('foo')->foo('bar'));
    assertType('CustomEloquentBuilderL11\CustomEloquentBuilder<CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::type('foo'));
    assertType('CustomEloquentBuilderL11\CustomEloquentBuilder<CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::whereEmail(['bar'])->type('foo')->whereEmail(['bar']));
    assertType('CustomEloquentBuilderL11\CustomEloquentBuilder<CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::whereEmail(['bar'])->categories(['foo'])->whereType(['bar']));
    assertType('CustomEloquentBuilderL11\CustomEloquentBuilder<CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::withTrashed());
    assertType('CustomEloquentBuilderL11\ModelWithCustomBuilder|null', ModelWithCustomBuilder::whereEmail(['bar'])->type('foo')->first());
    assertType('CustomEloquentBuilderL11\ModelWithCustomBuilder|null', ModelWithCustomBuilder::whereEmail(['bar'])->type('foo')->first());
    assertType('CustomEloquentBuilderL11\ModelWithCustomBuilder|null', $foo->customModels()->category('foo')->first());
    assertType('int', $foo->customModels()->count());
    assertType('bool', $foo->customModels()->exists());
    assertType('Illuminate\Database\Eloquent\Collection<int, CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::get());
    assertType('CustomEloquentBuilderL11\ModelWithCustomBuilder', ModelWithCustomBuilder::firstOrFail());
    assertType('CustomEloquentBuilderL11\ModelWithCustomBuilder', ModelWithCustomBuilder::findOrFail(1));
    assertType('Illuminate\Database\Eloquent\Collection<int, CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::findOrFail([1, 2, 3]));
    assertType('CustomEloquentBuilderL11\CustomEloquentBuilder<CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->has('users'));
    assertType('CustomEloquentBuilderL11\CustomEloquentBuilder<CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->orHas('users'));
    assertType('CustomEloquentBuilderL11\CustomEloquentBuilder<CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->doesntHave('users'));
    assertType('CustomEloquentBuilderL11\CustomEloquentBuilder<CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->orDoesntHave('users'));
    assertType('CustomEloquentBuilderL11\CustomEloquentBuilder<CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->whereHas('users'));
    assertType('CustomEloquentBuilderL11\CustomEloquentBuilder<CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->withWhereHas('users'));
    assertType('CustomEloquentBuilderL11\CustomEloquentBuilder<CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->orWhereHas('users'));
    assertType('CustomEloquentBuilderL11\CustomEloquentBuilder<CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->whereDoesntHave('users'));
    assertType('CustomEloquentBuilderL11\CustomEloquentBuilder<CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->orWhereDoesntHave('users'));
    assertType('CustomEloquentBuilderL11\CustomEloquentBuilder<CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->hasMorph('users', 'types'));
    assertType('CustomEloquentBuilderL11\CustomEloquentBuilder<CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->orHasMorph('users', 'types'));
    assertType('CustomEloquentBuilderL11\CustomEloquentBuilder<CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->doesntHaveMorph('users', 'types'));
    assertType('CustomEloquentBuilderL11\CustomEloquentBuilder<CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->orDoesntHaveMorph('users', 'types'));
    assertType('CustomEloquentBuilderL11\CustomEloquentBuilder<CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->whereHasMorph('users', 'types'));
    assertType('CustomEloquentBuilderL11\CustomEloquentBuilder<CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->orWhereHasMorph('users', 'types'));
    assertType('CustomEloquentBuilderL11\CustomEloquentBuilder<CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->whereDoesntHaveMorph('users', 'types'));
    assertType('CustomEloquentBuilderL11\CustomEloquentBuilder<CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->orWhereDoesntHaveMorph('users', 'types'));
    assertType('CustomEloquentBuilderL11\CustomEloquentBuilder<CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->mergeConstraintsFrom(ModelWithCustomBuilder::query()));
    assertType('CustomEloquentBuilderL11\CustomEloquentBuilder<CustomEloquentBuilderL11\ModelWithCustomBuilder>', ModelWithCustomBuilder::query()->whereHas('users')->type('foo'));
    assertType('Illuminate\Database\Eloquent\Collection<int, CustomEloquentBuilderL11\ModelWithCustomBuilderAndDocBlocks>', ModelWithCustomBuilderAndDocBlocks::query()->get());
    assertType('Illuminate\Database\Eloquent\Collection<int, CustomEloquentBuilderL11\ModelWithCustomBuilderAndDocBlocks>', ModelWithCustomBuilderAndDocBlocks::all());
    assertType('CustomEloquentBuilderL11\CustomBuilder2<CustomEloquentBuilderL11\ModelWithCustomBuilderAndDocBlocks>', ModelWithCustomBuilderAndDocBlocks::query());
    assertType('CustomEloquentBuilderL11\NonGenericBuilder', $nonGenericBuilder->skip(5));
    assertType('CustomEloquentBuilderL11\NonGenericBuilder', $nonGenericBuilder->category('foo'));

    assertType('CustomEloquentBuilderL11\ChildNonGenericBuilder', ModelWithNonGenericBuilder::where('email', 'bar'));
    assertType('CustomEloquentBuilderL11\ModelWithNonGenericBuilder|null', ModelWithNonGenericBuilder::where('email', 'bar')->first());
    assertType('CustomEloquentBuilderL11\ChildNonGenericBuilder', ModelWithNonGenericBuilder::where('email', 'bar')->orderBy('email'));
    assertType('Illuminate\Database\Eloquent\Collection<int, CustomEloquentBuilderL11\ModelWithNonGenericBuilder>', ModelWithNonGenericBuilder::get());
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
    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @param  CustomEloquentBuilder<static>  $query
     * @return CustomEloquentBuilder<static>
     */
    public function scopeFoo(CustomEloquentBuilder $query, string $foo): CustomEloquentBuilder
    {
        return $query->where(['email' => $foo]);
    }

    /** @return CustomEloquentBuilder<static> */
    public function testCustomBuilderReturnType(): CustomEloquentBuilder
    {
        return $this->where('email', 'bar');
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return CustomEloquentBuilder<static>
     */
    public function newEloquentBuilder($query): CustomEloquentBuilder
    {
        return new CustomEloquentBuilder($query);
    }
}

/**
 * @template TModel of ModelWithCustomBuilder
 *
 * @extends Builder<TModel>
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
     * @return $this
     */
    public function categories(array $categories): static
    {
        return $this->whereIn('category', $categories);
    }

    private function test(): void
    {
        $type = 'CustomEloquentBuilderL11\CustomEloquentBuilder<TModel of CustomEloquentBuilderL11\ModelWithCustomBuilder (class CustomEloquentBuilderL11\CustomEloquentBuilder, argument)>';
        assertType('static(CustomEloquentBuilderL11\CustomEloquentBuilder<TModel of CustomEloquentBuilderL11\ModelWithCustomBuilder (class CustomEloquentBuilderL11\CustomEloquentBuilder, argument)>)', $this->where('email', 'bar'));
        assertType('static(CustomEloquentBuilderL11\CustomEloquentBuilder<TModel of CustomEloquentBuilderL11\ModelWithCustomBuilder (class CustomEloquentBuilderL11\CustomEloquentBuilder, argument)>)', $this->where(['email' => 'bar']));
        assertType($type, $this->whereNull('finished_at'));
        assertType($type, $this->whereNotNull('finished_at'));
        assertType($type, $this->orderBy('name'));
        assertType($type, $this->orderByDesc('name'));
        assertType($type, $this->whereRaw('lower(email) = foo'));
        assertType($type, $this->join('user', 'user.id', '=', 'id'));
        assertType($type, $this->leftJoin('user', 'user.id', '=', 'id'));
        assertType($type, $this->rightJoin('user', 'user.id', '=', 'id'));
        assertType($type, $this->select('*'));
        assertType($type, $this->selectRaw('count(*) as count'));
//        assertType($type, $this->withTrashed());
    }
}

class FooModel extends Model
{
    /** @return HasMany<ModelWithCustomBuilder, $this> */
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
 * @template TModel of ModelWithCustomBuilderAndDocBlocks
 *
 * @extends Builder<TModel>
 */
class CustomBuilder2 extends Builder
{
}

/** @extends CustomEloquentBuilder<ModelWithNonGenericBuilder> */
class NonGenericBuilder extends CustomEloquentBuilder
{
    private function test(): void
    {
        $type = 'CustomEloquentBuilderL11\NonGenericBuilder';
        assertType($type, $this->whereNull('finished_at'));
        assertType($type, $this->whereNotNull('finished_at'));
        assertType($type, $this->orderBy('name'));
        assertType($type, $this->orderByDesc('name'));
        assertType($type, $this->whereRaw('lower(email) = foo'));
        assertType($type, $this->join('user', 'user.id', '=', 'id'));
        assertType($type, $this->leftJoin('user', 'user.id', '=', 'id'));
        assertType($type, $this->rightJoin('user', 'user.id', '=', 'id'));
        assertType($type, $this->select('*'));
        assertType($type, $this->selectRaw('count(*) as count'));
    }
}

class ChildNonGenericBuilder extends NonGenericBuilder
{
}

class ModelWithNonGenericBuilder extends ModelWithCustomBuilder
{
    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return ChildNonGenericBuilder
     */
    public function newEloquentBuilder($query): ChildNonGenericBuilder
    {
        return new ChildNonGenericBuilder($query);
    }
}
