<?php

namespace ModelPropertyUpdateVariants;

use App\Account;
use App\User;
use Illuminate\Database\Eloquent\Model;

function updateUser(User $user): void
{
    $user->updateOrFail(['foo' => 'bar']);
    $user->updateQuietly(['foo' => 'bar']);

    $user->updateOrFail(['email' => 'taylor@example.com', 'name' => 'Taylor']);
    $user->updateQuietly(['email' => 'taylor@example.com', 'name' => 'Taylor']);
    $user->updateOrFail(['email' => 'taylor@example.com'], ['touch' => false]);
    $user->updateQuietly(['email' => 'taylor@example.com'], ['touch' => false]);
    $user->updateOrFail([]);
    $user->updateQuietly([]);
    $user->updateOrFail();
    $user->updateQuietly();
}

/** @param array<string, mixed> $attributes */
function updateUserWithValidatedInput(User $user, array $attributes): void
{
    $user->updateOrFail($attributes);
    $user->updateQuietly($attributes);
}

class UpdateVariantsOnModel extends Model
{
    public function foo(): void
    {
        $this->updateOrFail(['foo' => 'bar']);
        $this->updateQuietly(['foo' => 'bar']);
    }

    public function unionMethod(User|Account $model): void
    {
        $model->updateOrFail(['foo' => 'bar']);
        $model->updateQuietly(['foo' => 'bar']);
        $model->updateOrFail(['id' => 5]);
        $model->updateQuietly(['id' => 5]);
    }
}
