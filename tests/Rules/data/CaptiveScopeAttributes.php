<?php

declare(strict_types=1);

namespace ScopeAttributeCompatibility;

use Illuminate\Container\Attributes\Scoped;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Http\Request;

#[Scoped]
final class TenantContext
{
}

final class Repository
{
    public function __construct(TenantContext $tenant)
    {
    }
}

#[Singleton]
final class BadDirectSingleton
{
    public function __construct(TenantContext $tenant)
    {
    }
}

#[Singleton]
final class BadIndirectSingleton
{
    public function __construct(Repository $repository)
    {
    }
}

#[Singleton]
final class BadRequestSingleton
{
    public function __construct(Request $request)
    {
    }
}

#[Singleton]
final class SafeSingleton
{
    public function __construct(\stdClass $object)
    {
    }
}

#[Singleton]
final class OptionalSingleton
{
    public function __construct(?TenantContext $tenant = null)
    {
    }
}

#[Scoped]
final class SafeScopedOwner
{
    public function __construct(TenantContext $tenant)
    {
    }
}

class ParentWithRequestDependency
{
    public function __construct(Request $request)
    {
    }
}

#[Singleton]
final class InheritedRequestSingleton extends ParentWithRequestDependency
{
}

class ParentWithScopedDependency
{
    public function __construct(TenantContext $tenant)
    {
    }
}

#[Singleton]
final class InheritedScopedSingleton extends ParentWithScopedDependency
{
}

#[Singleton]
final class ConstructorlessSingleton
{
}

final class OrdinaryRequestChild extends ParentWithRequestDependency
{
}
