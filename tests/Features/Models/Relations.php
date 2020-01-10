<?php

declare(strict_types=1);

namespace Tests\Features\Models;

use App\Account;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Relations
{
    public function testRelationWhere(): HasMany
    {
        return (new User())->accounts()->where('foo', 'bar');
    }

    public function testRelationWhereIn(): HasMany
    {
        return (new User())->accounts()->whereIn('id', [1, 2, 3]);
    }

    public function testRelationDynamicWhere(): HasMany
    {
        return (new User())->accounts()->whereFoo(['bar']);
    }

    public function testCreateWithRelation(): Account
    {
        /** @var User $user */
        $user = User::first();

        return $user->accounts()->create();
    }

    public function testCustomRelationCreate(): Account
    {
        /** @var User $user */
        $user = User::first();

        return $user->syncableRelation()->create();
    }

    public function testCreateWithGettingModelFromMethod(): Account
    {
        return $this->getUser()->accounts()->create();
    }

    private function getUser(): User
    {
        return User::firstOrFail();
    }
}

/**
 * @property-read User $relation
 */
class RelationCreateExample extends Model
{
    public function relation(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function addRelation(): User
    {
        return $this->relation()->create([]);
    }
}

class ModelWithoutPropertyAnnotation extends Model
{
    public function relation(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function addRelation(): User
    {
        return $this->relation()->create([]);
    }
}

class TestRelationCreateOnExistingModel
{
    /** @var User */
    private $user;

    public function testRelationCreateOnExistingModel(): Account
    {
        return $this->user->accounts()->create();
    }
}
