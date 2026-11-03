<?php

declare(strict_types=1);

namespace OctaneSemanticShared;

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;

final class NamedShared
{
    public function __construct(public Request $request)
    {
    }
}

final class PositionalShared
{
    public function __construct(public Request $request)
    {
    }
}

final class SharedViaBindIf
{
    public function __construct(public Request $request)
    {
    }
}

final class TransientDefault
{
    public function __construct(public Request $request)
    {
    }
}

final class TransientExplicit
{
    public function __construct(public Request $request)
    {
    }
}

function register(Container $app): void
{
    $app->bind(NamedShared::class, shared: true);
    $app->bind(PositionalShared::class, null, true);
    $app->bindIf(SharedViaBindIf::class, null, true);
    $app->bind(TransientDefault::class);
    $app->bind(TransientExplicit::class, shared: false);
}
