<?php

declare(strict_types=1);

namespace ScopeAcrossFiles;

use Illuminate\Contracts\Container\Container;

function register(Container $app): void
{
    $app->scoped(ScopedContext::class);
    $app->singleton(ContextOwner::class);

    $app->scoped(ScopedContract::class, ScopedImplementation::class);
    $app->singleton(ContractOwner::class);

    $app->bind(TransientContract::class, TransientImplementation::class);
    $app->singleton(TransientOwner::class);

    $app->bind(DelegateContract::class, ScopedDelegate::class);
    $app->singleton(DelegateOwner::class);

    // The class attribute is trumped by an explicit shared registration.
    $app->singleton(OverriddenScopedContext::class);
    $app->singleton(SafeOwner::class);

    // A class marked #[Singleton] can be explicitly registered as scoped.
    $app->scoped(AttributeOwner::class);

    // Across providers, conflicting registration order is unknown.
    $app->singleton(AmbiguousOwner::class);
    $app->scoped(AmbiguousOwner::class);
}
