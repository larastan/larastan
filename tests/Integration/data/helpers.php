<?php

use App\User;

function transformAcceptsNonNullableCallable(): void
{
    transform(User::first(), fn (User $user) => $user->toArray());
}
