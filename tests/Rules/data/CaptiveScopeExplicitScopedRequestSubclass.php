<?php

declare(strict_types=1);

namespace ScopeCompatibilityRequestSubclassScoped;

use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;

#[Scoped]
final class ExplicitScopedRequest extends Request
{
}

final class CaptiveSingleton
{
    public function __construct(ExplicitScopedRequest $request)
    {
    }
}

function register(Container $app): void
{
    $app->singleton(CaptiveSingleton::class);
}
