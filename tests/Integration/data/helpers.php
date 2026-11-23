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
    }, function (int $attempt, \Throwable $e): int {
        return 0;
    }, function (\Throwable $e): bool {
        return true;
    });
}

function translationsAcceptClosureReplacements(): void
{
    trans('<wrap>Hello</wrap>', ['wrap' => fn (string $chunk): string => '<b>' . $chunk . '</b>']);
    __('<wrap>Hello</wrap>', ['wrap' => fn (string $chunk): string => '<b>' . $chunk . '</b>']);
}
