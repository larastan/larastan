<?php

use App\User;

function transformAcceptsNonNullableCallable(): void
{
    transform(User::first(), fn (User $user) => $user->toArray());
}

function retryAcceptsCallableAsSleepMilliseconds(): void
{
    retry(5, function (int $attempt): bool {
        return false;
    }, function (int $attempt, Exception $e): int {
        return 0;
    }, function (Exception $e): bool {
        return true;
    });
}
