<?php

declare(strict_types=1);

namespace ScopeCompatibilitySymfonyRequest;

use Illuminate\Contracts\Container\Container;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

final class SymfonyAliasConsumer
{
    public function __construct(SymfonyRequest $request)
    {
    }
}

final class DependencyWrapper
{
    public function __construct(SymfonyRequest $request)
    {
    }
}

final class TransitiveConsumer
{
    public function __construct(DependencyWrapper $wrapper)
    {
    }
}

final class CustomSymfonyRequest extends SymfonyRequest
{
}

final class UnboundSubclassConsumer
{
    public function __construct(CustomSymfonyRequest $request)
    {
    }
}

final class OptionalSymfonyAliasConsumer
{
    public function __construct(?SymfonyRequest $request = null)
    {
    }
}

function register(Container $app): void
{
    $app->singleton(SymfonyAliasConsumer::class);
    $app->singleton(TransitiveConsumer::class);
    $app->singleton(UnboundSubclassConsumer::class);
    $app->singleton(OptionalSymfonyAliasConsumer::class);
}
