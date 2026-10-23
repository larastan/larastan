<?php

declare(strict_types=1);

namespace ScopeCompatibilityNullableRequest;

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;

final class RequiredNullableConsumer
{
    public function __construct(?Request $request)
    {
    }
}

final class OptionalNullableConsumer
{
    public function __construct(?Request $request = null)
    {
    }
}

final class BoundOptionalRequestConsumer
{
    public function __construct(public ?Request $request = null)
    {
    }
}

function register(Container $app): void
{
    $app->singleton(RequiredNullableConsumer::class);
    $app->singleton(OptionalNullableConsumer::class);
    $app->singleton(BoundOptionalRequestConsumer::class);
}
