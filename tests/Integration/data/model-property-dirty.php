<?php

namespace ModelPropertyDirty;

use App\Account;
use App\User;
use Illuminate\Database\Eloquent\Model;

function checkUser(User $user): void
{
    $user->isDirty('foo');
    $user->isClean('foo');
    $user->wasChanged('foo');
    $user->isDirty(['email', 'foo']);
    $user->isClean(['email', 'foo']);
    $user->wasChanged(['email', 'foo']);

    $user->isDirty();
    $user->isClean();
    $user->wasChanged();
    $user->isDirty(null);
    $user->isDirty('email');
    $user->isClean('email');
    $user->wasChanged('email');
    $user->isDirty(['email', 'name']);
    $user->isClean(['email', 'name']);
    $user->wasChanged(['email', 'name']);
    $user->isDirty('email', 'name');
    $user->isClean('email', 'name');
    $user->wasChanged('email', 'name');
}

/** @param list<string> $attributes */
function checkUserWithArbitraryStrings(User $user, string $attribute, array $attributes): void
{
    $user->isDirty($attribute);
    $user->isClean($attribute);
    $user->wasChanged($attribute);
    $user->isDirty($attributes);
    $user->isClean($attributes);
    $user->wasChanged($attributes);
}

function checkUnion(User|Account $model): void
{
    $model->isDirty('foo');
    $model->isDirty('id');
}

class DirtyOnModel extends Model
{
    public function foo(): void
    {
        $this->isDirty('foo');
        $this->isClean('foo');
        $this->wasChanged('foo');
    }
}

function checkBaseModel(Model $model): void
{
    $model->isDirty('status');
}

/**
 * @template TModel of Model
 *
 * @param TModel $model
 */
function checkGenericModel(Model $model): void
{
    $model->isDirty('status');
}

/** @property int $tenant_id */
abstract class DirtyOnAbstractModel extends Model
{
    public function foo(): void
    {
        $this->isDirty('tenant_id');
        $this->isDirty('foo');
    }
}
