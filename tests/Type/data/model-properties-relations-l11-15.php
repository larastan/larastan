<?php

namespace ModelPropertiesRelationsL1115;

use App\Account;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;

use function PHPStan\Testing\assertType;

/** @property string $name */
class Foo extends Model
{
    /** @return HasMany<Bar, $this> */
    public function hasManyRelation(): HasMany
    {
        return $this->hasMany(Bar::class);
    }

    /** @return HasManyThrough<Bar, $this> */
    public function hasManyThroughRelation(): HasManyThrough
    {
        return $this->hasManyThrough(Bar::class, User::class);
    }

    /** @return HasOneThrough<Baz, User, $this> */
    public function hasOneThroughRelation(): HasOneThrough
    {
        return $this->hasOneThrough(Baz::class, User::class);
    }

    /** @return HasMany<Bar, $this>|BelongsTo<Bar, $this> */
    public function relationReturningUnion(): HasMany|BelongsTo
    {
        return $this->name === 'foo' ? $this->hasMany(Bar::class) : $this->belongsTo(Baz::class);
    }

    /** @return HasMany<Bar, $this>|BelongsTo<Baz, Foo> */
    public function relationReturningUnion2(): HasMany|BelongsTo
    {
        return $this->name === 'foo' ? $this->hasMany(Bar::class) : $this->belongsTo(Baz::class);
    }

    /** @return Ancestors<Foo, $this> */
    public function ancestors(): Ancestors
    {
        //
    }
}

/**
 * @property Foo $belongsToRelation
 */
class Bar extends Model
{
    /** @return BelongsTo<Foo, Bar> */
    public function belongsToRelation(): BelongsTo
    {
        return $this->belongsTo(Foo::class);
    }

    /** @return MorphTo<Model, Bar> */
    public function morphToRelation(): MorphTo
    {
        return $this->morphTo('foo');
    }

    /** @return MorphTo<User|Account, Bar> */
    public function morphToUnionRelation(): MorphTo
    {
        return $this->morphTo('foo');
    }
}

class Baz extends Model
{
}

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends HasMany<TRelatedModel, TDeclaringModel>
 */
class Ancestors extends HasMany
{
}

function test(Foo $foo, Bar $bar, Account $account): void
{
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelPropertiesRelationsL1115\Bar>', $foo->hasManyRelation);
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelPropertiesRelationsL1115\Bar>', $foo->hasManyThroughRelation);
    assertType('ModelPropertiesRelationsL1115\Baz|null', $foo->hasOneThroughRelation);
    assertType('ModelPropertiesRelationsL1115\Foo', $bar->belongsToRelation);
    assertType('Illuminate\Database\Eloquent\Model|null', $bar->morphToRelation);
    assertType('App\Account|App\User|null', $bar->morphToUnionRelation);
    assertType('ModelPropertiesRelationsL1115\Bar|null', $foo->hasManyRelation->first());
    assertType('ModelPropertiesRelationsL1115\Bar|null', $foo->hasManyRelation()->find(1));
    assertType('App\User|null', $account->ownerRelation);
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelPropertiesRelationsL1115\Bar>|ModelPropertiesRelationsL1115\Bar|null', $foo->relationReturningUnion);
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelPropertiesRelationsL1115\Bar>|ModelPropertiesRelationsL1115\Baz|null', $foo->relationReturningUnion2);
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelPropertiesRelationsL1115\Foo>', $foo->ancestors);
}

