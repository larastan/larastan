<?php

declare(strict_types=1);

namespace Tests\Features\Properties;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class ModelRelationsExtension
{
    /** @return iterable<OtherDummyModel>&Collection */
    public function testHasMany()
    {
        /** @var DummyModel $dummyModel */
        $dummyModel = DummyModel::firstOrFail();

        return $dummyModel->hasManyRelation;
    }

    public function testHasManyForEach() : OtherDummyModel
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

    /** @return iterable<OtherDummyModel>&Collection */
    public function testHasManyThroughRelation()
    {
        /** @var DummyModel $dummyModel */
        $dummyModel = DummyModel::firstOrFail();

        return $dummyModel->hasManyThroughRelation;
    }

    public function testBelongsTo() : ?DummyModel
    {
        /** @var OtherDummyModel $otherDummyModel */
        $otherDummyModel = OtherDummyModel::firstOrFail();

        return $otherDummyModel->belongsToRelation;
    }

    public function testMorphTo() : DummyModel
    {
        /** @var OtherDummyModel $otherDummyModel */
        $otherDummyModel = OtherDummyModel::firstOrFail();

        return $otherDummyModel->morphToRelation;
    }

    /** @return mixed */
    public function testRelationWithoutReturnType()
    {
        /** @var DummyModel $dummyModel */
        $dummyModel = DummyModel::firstOrFail();

        return $dummyModel->relationWithoutReturnType;
    }
}

class DummyModel extends Model
{
    public function hasManyRelation() : HasMany
    {
        return $this->hasMany(OtherDummyModel::class);
    }

    public function hasManyThroughRelation() : HasManyThrough
    {
        return $this->hasManyThrough(OtherDummyModel::class, User::class);
    }

    public function relationWithoutReturnType()
    {
        return $this->hasMany(OtherDummyModel::class);
    }
}

class OtherDummyModel extends Model
{
    public function belongsToRelation() : BelongsTo
    {
        return $this->belongsTo(DummyModel::class);
    }

    public function morphToRelation() : MorphTo
    {
        return $this->morphTo('foo');
    }
}
