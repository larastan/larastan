<?php

namespace NoImplicitQueryBuilderCallCustomBuilder;

use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** @extends Builder<User> */
class UserBuilder extends Builder
{
    public function named(string $name): static
    {
        return $this->where('name', $name);
    }
}

#[UseEloquentBuilder(UserBuilder::class)]
class User extends Model
{
}

function customBuilder(User $user): void
{
    User::query()->named('Jane');
    $user->newQuery()->named('Jane');
    User::query()->named('Jane');
    $user->newQuery()->named('Jane');
    User::undefinedMethod();
}
