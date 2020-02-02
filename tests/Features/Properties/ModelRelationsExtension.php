<?php

declare(strict_types=1);

namespace Tests\Features\Properties;

use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModelRelationsExtension
{
    /** @return Collection<OtherDummyModel> */
    public function testHasMany()
    {
        /** @var DummyModel $dummyModel */
        $dummyModel = DummyModel::firstOrFail();

        return $dummyModel->hasManyRelation;
    }

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

    /** @return Collection<OtherDummyModel> */
    public function testHasManyThroughRelation()
    {
        /** @var DummyModel $dummyModel */
        $dummyModel = DummyModel::firstOrFail();

        return $dummyModel->hasManyThroughRelation;
    }

    public function testBelongsTo(): ?DummyModel
    {
        /** @var OtherDummyModel $otherDummyModel */
        $otherDummyModel = OtherDummyModel::firstOrFail();

        return $otherDummyModel->belongsToRelation;
    }

    /** @return mixed */
    public function testMorphTo()
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

    public function testModelCanOverwriteRelationPropertyWithAnnotation(): DummyModel
    {
        /** @var OtherDummyModel $otherDummyModel */
        $otherDummyModel = OtherDummyModel::firstOrFail();

        return $otherDummyModel->belongsToRelationWithoutNull;
    }

    public function testCollectionMethodFirstOnRelation(): ?OtherDummyModel
    {
        /** @var DummyModel $dummyModel */
        $dummyModel = DummyModel::firstOrFail();

        return $dummyModel->hasManyRelation->first();
    }

    public function testCollectionMethodFindOnRelation(): ?OtherDummyModel
    {
        /** @var DummyModel $dummyModel */
        $dummyModel = DummyModel::firstOrFail();

        return $dummyModel->hasManyRelation->find(1);
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
    public function hasManyRelation(): HasMany
    {
        return $this->hasMany(OtherDummyModel::class);
    }

    public function hasManyThroughRelation(): HasManyThrough
    {
        return $this->hasManyThrough(OtherDummyModel::class, User::class);
    }

    /** @return mixed */
    public function relationWithoutReturnType()
    {
        return $this->hasMany(OtherDummyModel::class);
    }
}

/** @property-read DummyModel $belongsToRelationWithoutNull */
class OtherDummyModel extends Model
{
    public function belongsToRelation(): BelongsTo
    {
        return $this->belongsTo(DummyModel::class);
    }

    public function belongsToRelationWithoutNull(): BelongsTo
    {
        return $this->belongsTo(DummyModel::class);
    }

    public function morphToRelation(): MorphTo
    {
        return $this->morphTo('foo');
    }
}
