<?php

declare(strict_types=1);

namespace ScopeCompatibilityAttributePriority;

use Illuminate\Container\Attributes\Scoped;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Container\Container;

#[Singleton]
#[Scoped]
final class SingletonWins
{
}

#[Scoped]
final class ActuallyScoped
{
}

final class HoldsSingletonWins
{
    public function __construct(SingletonWins $instance)
    {
    }
}

final class HoldsScoped
{
    public function __construct(ActuallyScoped $instance)
    {
    }
}

function register(Container $app): void
{
    $app->singleton(HoldsSingletonWins::class);
    $app->singleton(HoldsScoped::class);
}
