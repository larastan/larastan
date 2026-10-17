<?php

declare(strict_types=1);

namespace ScopeCompatibilityRegistrationOverrides;

use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Container\Container;

final class PlainContext
{
}

final class PlainCapturingSingleton
{
    public function __construct(PlainContext $context)
    {
    }
}

#[Scoped]
final class OverriddenScopedContext
{
}

final class SafeCapturingSingleton
{
    public function __construct(OverriddenScopedContext $context)
    {
    }
}

function register(Container $app): void
{
    $app->scoped(PlainContext::class);
    $app->singleton(PlainCapturingSingleton::class);
    $app->singleton(OverriddenScopedContext::class);
    $app->singleton(SafeCapturingSingleton::class);
}
