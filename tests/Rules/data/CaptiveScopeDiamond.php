<?php

declare(strict_types=1);

namespace ScopeCompatibilityDiamond;

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;

// Visit Shared at the depth limit first, then through ShortPath.
// A global visited set hides the second, valid route.

final class Root
{
    public function __construct(Deep1 $deep, ShortPath $short)
    {
    }
}

final class Deep1
{
    public function __construct(Deep2 $next)
    {
    }
}

final class Deep2
{
    public function __construct(Deep3 $next)
    {
    }
}

final class Deep3
{
    public function __construct(Deep4 $next)
    {
    }
}

final class Deep4
{
    public function __construct(Deep5 $next)
    {
    }
}

final class Deep5
{
    public function __construct(Deep6 $next)
    {
    }
}

final class Deep6
{
    public function __construct(Shared $next)
    {
    }
}

final class ShortPath
{
    public function __construct(Shared $shared)
    {
    }
}

final class Shared
{
    public function __construct(Intermediate $next)
    {
    }
}

final class Intermediate
{
    public function __construct(Request $request)
    {
    }
}

function register(Container $app): void
{
    $app->singleton(Root::class);
}

final class PrioritizedRoot
{
    public function __construct(Intermediate $indirect, Request $direct)
    {
    }
}

function registerPrioritized(Container $app): void
{
    $app->singleton(PrioritizedRoot::class);
}
