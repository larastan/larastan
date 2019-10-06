<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use App\Account;
use App\User;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ModelExtension
{
    public function testCreateWithRelation() : Account
    {
        /** @var User $user */
        $user = User::first();

        return $user->accounts()->create();
    }
}

/**
 * @property-read User $relation
 */
class RelationCreateExample extends Model
{
    public function relation() : HasMany
    {
        return $this->hasMany(User::class);
    }

    public function addRelation() : User
    {
        return $this->relation()->create([]);
    }
}

class ModelWithoutPropertyAnnotation extends Model
{
    public function relation() : HasMany
    {
        return $this->hasMany(User::class);
    }

    public function addRelation() : User
    {
        return $this->relation()->create([]);
    }
}
