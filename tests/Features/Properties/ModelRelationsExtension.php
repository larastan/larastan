<?php

declare(strict_types=1);

namespace Tests\Features\Properties;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModelRelationsExtension
{
    public function testHasManyForEach(): OtherDummyModel
    {
        /** @var DummyModel $dummyModel */
        $dummyModel = DummyModel::firstOrFail();

        foreach ($dummyModel->hasManyRelation as $related) {
            if (random_int(0, 1)) {
                return $related;
            }
        }

        return new OtherDummyModel;
    }

    public function testModelRelationForeach(DummyModel $dummyModel): ?OtherDummyModel
    {
        foreach ($dummyModel->hasManyRelation as $item) {
            if (random_int(0, 1)) {
                return $item;
            }
        }

        return null;
    }
}

class DummyModel extends Model
{
    /** @return HasMany<OtherDummyModel> */
    public function hasManyRelation(): HasMany
    {
        return $this->hasMany(OtherDummyModel::class);
    }

    /** @return HasManyThrough<OtherDummyModel> */
    public function hasManyThroughRelation(): HasManyThrough
    {
        return $this->hasManyThrough(OtherDummyModel::class, User::class);
    }
}

/**
 * @property DummyModel $belongsToRelation
 */
class OtherDummyModel extends Model
{
    /** @return BelongsTo<DummyModel, OtherDummyModel> */
    public function belongsToRelation(): BelongsTo
    {
        return $this->belongsTo(DummyModel::class);
    }

    /** @return MorphTo<Model, OtherDummyModel> */
    public function morphToRelation(): MorphTo
    {
        return $this->morphTo('foo');
    }
}
