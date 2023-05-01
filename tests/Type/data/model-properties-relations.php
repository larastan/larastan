<?php

namespace ModelPropertiesRelations;

use App\Account;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;

use function PHPStan\Testing\assertType;

function foo(Foo $foo, Bar $bar, Account $account): void
{
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelPropertiesRelations\Bar>', $foo->hasManyRelation);
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelPropertiesRelations\Bar>', $foo->hasManyThroughRelation);
    assertType('ModelPropertiesRelations\Foo', $bar->belongsToRelation);
    assertType('mixed', $bar->morphToRelation);
    assertType('App\Account|App\User', $bar->morphToUnionRelation);
    assertType('ModelPropertiesRelations\Bar|null', $foo->hasManyRelation->first());
    assertType('ModelPropertiesRelations\Bar|null', $foo->hasManyRelation()->find(1));
    assertType('App\User|null', $account->ownerRelation);
    assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Database\Eloquent\Model>|Illuminate\Database\Eloquent\Model|null', $foo->relationReturningUnion);
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelPropertiesRelations\Bar>|ModelPropertiesRelations\Baz|null', $foo->relationReturningUnion2);
}

/** @property string $name */
class Foo extends Model
{
    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<\ModelPropertiesRelations\Bar> */
    public function hasManyRelation(): HasMany
    {
        return $this->hasMany(Bar::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasManyThrough<\ModelPropertiesRelations\Bar> */
    public function hasManyThroughRelation(): HasManyThrough
    {
        return $this->hasManyThrough(Bar::class, User::class);
    }

    public function relationReturningUnion(): HasMany|BelongsTo
    {
        return $this->name === 'foo' ? $this->hasMany(Bar::class) : $this->belongsTo(Baz::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<\ModelPropertiesRelations\Bar>|\Illuminate\Database\Eloquent\Relations\BelongsTo<\ModelPropertiesRelations\Baz, \ModelPropertiesRelations\Foo> */
    public function relationReturningUnion2(): HasMany|BelongsTo
    {
        return $this->name === 'foo' ? $this->hasMany(Bar::class) : $this->belongsTo(Baz::class);
    }
}

/**
 * @property \ModelPropertiesRelations\Foo $belongsToRelation
 */
class Bar extends Model
{
    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\ModelPropertiesRelations\Foo, \ModelPropertiesRelations\Bar> */
    public function belongsToRelation(): BelongsTo
    {
        return $this->belongsTo(Foo::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\MorphTo<\Illuminate\Database\Eloquent\Model, \ModelPropertiesRelations\Bar> */
    public function morphToRelation(): MorphTo
    {
        return $this->morphTo('foo');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\MorphTo<(\App\User|\App\Account), \ModelPropertiesRelations\Bar> */
    public function morphToUnionRelation(): MorphTo
    {
        return $this->morphTo('foo');
    }
}

class Baz extends Model
{
}
