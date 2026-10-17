<?php

declare(strict_types=1);

namespace ScopeCompatibilityExplicitSingleton;

use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;

#[Scoped]
final class ScopeMarkedButNoCaptiveDependency
{
    // Explicit singleton registration overrides the scoped lifetime marker.
}

#[Scoped]
final class ScopeMarkedAndCapturesRequest
{
    public function __construct(Request $request)
    {
    }
}

function register(Container $app): void
{
    $app->singleton(ScopeMarkedButNoCaptiveDependency::class);
    $app->singleton(ScopeMarkedAndCapturesRequest::class);
}
