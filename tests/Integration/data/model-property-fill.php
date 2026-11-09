<?php

namespace ModelPropertyFill;

use App\User;
use Illuminate\Database\Eloquent\Model;

function fillUser(User $user): void
{
    $user->fill(['foo' => 'bar']);
    $user->forceFill(['foo' => 'bar'])->save();

    $user->fill(['email' => 'taylor@example.com', 'name' => 'Taylor']);
    $user->forceFill(['email' => 'taylor@example.com'])->save();
    $user->fill([]);
}

/** @param array<string, mixed> $attributes */
function fillUserWithValidatedInput(User $user, array $attributes): void
{
    $user->fill($attributes);
    $user->forceFill($attributes);
}

class FillOnModel extends Model
{
    public function foo(): void
    {
        $this->fill(['foo' => 'bar']);
        $this->forceFill(['foo' => 'bar']);
    }
}
