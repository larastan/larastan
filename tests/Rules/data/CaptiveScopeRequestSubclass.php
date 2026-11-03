<?php

declare(strict_types=1);

namespace ScopeCompatibilityRequestSubclass;

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;

// Laravel supplies an active request object. A custom subclass must retain
// the same short-lived semantics, even on Laravel 11 without DI attributes.
final class CustomRequest extends Request
{
}

final class SingletonConsumer
{
    public function __construct(CustomRequest $request)
    {
    }
}

final class ScopedConsumer
{
    public function __construct(CustomRequest $request)
    {
    }
}

function register(Container $app): void
{
    $app->singleton(SingletonConsumer::class);
    $app->scoped(ScopedConsumer::class);
}
