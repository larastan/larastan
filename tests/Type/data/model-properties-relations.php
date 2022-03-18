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

function foo(Foo $dummyModel, Bar $otherFoo, Account $account): void {
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelPropertiesRelations\Bar>', $dummyModel->hasManyRelation);
    assertType('Illuminate\Database\Eloquent\Collection<int, ModelPropertiesRelations\Bar>', $dummyModel->hasManyThroughRelation);
    assertType('ModelPropertiesRelations\Foo', $otherFoo->belongsToRelation);
    assertType('mixed', $otherFoo->morphToRelation);
    assertType('ModelPropertiesRelations\Bar|null', $dummyModel->hasManyRelation->first());
    assertType('ModelPropertiesRelations\Bar|null', $dummyModel->hasManyRelation()->find(1));
    assertType('App\User|null', $account->ownerRelation);
}

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
}
