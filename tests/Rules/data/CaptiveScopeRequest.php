<?php

declare(strict_types=1);

namespace ScopeCompatibilityRequest;

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;

final class RequestConsumer
{
    public function __construct(Request $request)
    {
    }
}

function register(Container $app): void
{
    $app->singleton(RequestConsumer::class);
}
