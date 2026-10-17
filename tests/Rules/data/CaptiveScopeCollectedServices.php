<?php

declare(strict_types=1);

namespace ScopeAcrossFiles;

use Illuminate\Container\Attributes\Scoped;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Http\Request;

final class ScopedContext
{
}

final class ContextOwner
{
    public function __construct(public ScopedContext $context)
    {
    }
}

interface ScopedContract
{
}

final class ScopedImplementation implements ScopedContract
{
}

final class ContractOwner
{
    public function __construct(public ScopedContract $context)
    {
    }
}

interface TransientContract
{
}

final class TransientImplementation implements TransientContract
{
    public function __construct(Request $request)
    {
    }
}

final class TransientOwner
{
    public function __construct(public TransientContract $context)
    {
    }
}

interface DelegateContract
{
}

#[Scoped]
final class ScopedDelegate implements DelegateContract
{
}

final class DelegateOwner
{
    public function __construct(public DelegateContract $context)
    {
    }
}

#[Scoped]
final class OverriddenScopedContext
{
}

final class SafeOwner
{
    public function __construct(public OverriddenScopedContext $context)
    {
    }
}

#[Singleton]
final class AttributeOwner
{
    public function __construct(Request $request)
    {
    }
}

final class AmbiguousOwner
{
    public function __construct(Request $request)
    {
    }
}
