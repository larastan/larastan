<?php

declare(strict_types=1);

namespace ScopeFactoryBinding;

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;

class FactoryBoundService
{
    public function __construct(public Request $request)
    {
    }
}

final class SafeFactoryResult extends FactoryBoundService
{
    public function __construct()
    {
        // A real factory need not invoke the abstract's constructor.
    }
}

final class SingletonWithFactory
{
    public function __construct(public FactoryBoundService $service)
    {
    }
}

class ClosureBoundService
{
    public function __construct(public Request $request)
    {
    }
}

final class SafeClosureResult extends ClosureBoundService
{
    public function __construct()
    {
    }
}

final class SingletonWithClosure
{
    public function __construct(public ClosureBoundService $service)
    {
    }
}

final class SelfBoundService
{
    public function __construct(public Request $request)
    {
    }
}

final class SingletonWithSelfBinding
{
    public function __construct(public SelfBoundService $service)
    {
    }
}

final class FactoryScopedContext
{
}

final class SingletonWithScopedFactory
{
    public function __construct(public FactoryScopedContext $context)
    {
    }
}

function register(Container $app): void
{
    // The arrow factory returns an object with no Request dependency.
    $app->bind(FactoryBoundService::class, static fn (): SafeFactoryResult => new SafeFactoryResult());
    $app->singleton(SingletonWithFactory::class);

    // A full Closure has the same semantics: never inspect its abstract's constructor.
    $app->bind(ClosureBoundService::class, static function (): SafeClosureResult {
        return new SafeClosureResult();
    });
    $app->singleton(SingletonWithClosure::class);

    // Controls: the omitted concrete really does cause constructor autowiring.
    $app->bind(SelfBoundService::class);
    $app->singleton(SingletonWithSelfBinding::class);

    // A scoped factory still has a scoped lifetime despite unknown implementation.
    $app->scoped(FactoryScopedContext::class, static fn (): FactoryScopedContext => new FactoryScopedContext());
    $app->singleton(SingletonWithScopedFactory::class);
}
