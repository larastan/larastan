<?php

namespace ModelPropertiesRelations;

use App\Account;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;

use function PHPStan\Testing\assertType;

function foo(Foo $foo, Bar $bar, Account $account, ChildBaz $childBaz): void
{
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelPropertiesRelations\Bar>', $foo->hasManyRelation);
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelPropertiesRelations\Bar>', $foo->hasManyThroughRelation);
    assertType('ModelPropertiesRelations\Baz|null', $foo->hasOneThroughRelation);
    assertType('ModelPropertiesRelations\Foo', $bar->belongsToRelation);
    assertType('ModelPropertiesRelations\Foo|null', $childBaz->foo);
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<ModelPropertiesRelations\Foo, ModelPropertiesRelations\ChildBaz>', $childBaz->foo());
    assertType('mixed', $bar->morphToRelation);
    assertType('App\Account|App\User', $bar->morphToUnionRelation);
    assertType('ModelPropertiesRelations\Bar|null', $foo->hasManyRelation->first());
    assertType('ModelPropertiesRelations\Bar|null', $foo->hasManyRelation()->find(1));
    assertType('App\User|null', $account->ownerRelation);
    assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Database\Eloquent\Model>|Illuminate\Database\Eloquent\Model|null', $foo->relationReturningUnion);
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelPropertiesRelations\Bar>|ModelPropertiesRelations\Baz|null', $foo->relationReturningUnion2);
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelPropertiesRelations\Foo>', $foo->ancestors);
}

/** @property string $name */
class Foo extends Model
{
    /** @return HasMany<Bar> */
    public function hasManyRelation(): HasMany
    {
        return $this->hasMany(Bar::class);
    }

    /** @return HasManyThrough<Bar> */
    public function hasManyThroughRelation(): HasManyThrough
    {
        return $this->hasManyThrough(Bar::class, User::class);
    }

    /** @return HasOneThrough<Baz> */
    public function hasOneThroughRelation(): HasOneThrough
    {
        return $this->hasOneThrough(Baz::class, User::class);
    }

    public function relationReturningUnion(): HasMany|BelongsTo
    {
        return $this->name === 'foo' ? $this->hasMany(Bar::class) : $this->belongsTo(Baz::class);
    }

    /** @return HasMany<Bar>|BelongsTo<Baz, Foo> */
    public function relationReturningUnion2(): HasMany|BelongsTo
    {
        return $this->name === 'foo' ? $this->hasMany(Bar::class) : $this->belongsTo(Baz::class);
    }

    /** @return Ancestors<Foo> */
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
    /** @return BelongsTo<Foo, $this> */
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
    /** @return BelongsTo<Foo, $this> */
    public function foo(): BelongsTo
    {
        return $this->belongsTo(Foo::class);
    }
}

class ChildBaz extends Baz
{
}

class Ancestors extends HasMany
{
}
