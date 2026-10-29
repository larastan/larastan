<?php

declare(strict_types=1);

namespace OctaneSemanticRoots;

use Illuminate\Container\Attributes\Scoped;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;

#[Singleton]
class RootFactory
{
    public function __construct(public Request $request)
    {
    }
}

final class SafeFactoryConcrete extends RootFactory
{
    public function __construct()
    {
    }
}

#[Singleton]
class RootMapped
{
    public function __construct(public Request $request)
    {
    }
}

final class SafeMappedConcrete extends RootMapped
{
    public function __construct()
    {
    }
}

#[Singleton]
class RootMappedCaptive
{
    public function __construct()
    {
    }
}

final class CaptiveMappedConcrete extends RootMappedCaptive
{
    public function __construct(public Request $request)
    {
    }
}

#[Singleton]
class RootToScoped
{
}

#[Scoped]
final class ScopedMappedConcrete extends RootToScoped
{
}

#[Singleton]
class RootInstance
{
    public function __construct(public Request $request)
    {
    }
}

final class SafeExistingInstance extends RootInstance
{
    public function __construct()
    {
    }
}

#[Singleton]
final class RootSelf
{
    public function __construct(public Request $request)
    {
    }
}

function register(Container $app): void
{
    $app->bind(RootFactory::class, static fn (): SafeFactoryConcrete => new SafeFactoryConcrete());
    $app->bind(RootMapped::class, SafeMappedConcrete::class);
    $app->bind(RootMappedCaptive::class, CaptiveMappedConcrete::class);
    $app->bind(RootToScoped::class, ScopedMappedConcrete::class);
    $app->instance(RootInstance::class, new SafeExistingInstance());
    $app->bind(RootSelf::class);
}
