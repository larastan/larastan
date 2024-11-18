<?php

namespace ModelRelationsL1131;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

use function PHPStan\Testing\assertType;

class Car extends Model {}

class Mechanic extends Model
{
    /** @return HasOne<Car, $this> */
    public function car(): HasOne
    {
        return $this->hasOne(Car::class);
    }
}

class User extends Model
{
    /** @return HasOne<Mechanic, $this> */
    public function mechanic(): HasOne
    {
        return $this->hasOne(Mechanic::class);
    }

    /** @return HasOneThrough<\ModelRelationsL11\Car, \ModelRelationsL11\Mechanic, $this> */
    public function car(): HasOneThrough
    {
        $through = $this->through($this->mechanic());
        assertType(
            'Illuminate\Database\Eloquent\PendingHasThroughRelationship<ModelRelationsL1131\Mechanic, $this(ModelRelationsL1131\User), Illuminate\Database\Eloquent\Relations\HasOne<ModelRelationsL1131\Mechanic, $this(ModelRelationsL1131\User)>>',
            $through,
        );
        assertType(
            'Illuminate\Database\Eloquent\Relations\HasOneThrough<ModelRelationsL1131\Car, ModelRelationsL1131\Mechanic, $this(ModelRelationsL1131\User)>',
            $through->has(function ($mechanic) {
                assertType('ModelRelationsL1131\Mechanic', $mechanic);

                return $mechanic->car();
            }),
        );

        return $this->hasOneThrough(Car::class, Mechanic::class);
    }
}
