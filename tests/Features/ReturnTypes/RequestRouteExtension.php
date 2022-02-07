<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class RequestExtension
{
    /** @phpstan-return Route|null */
    public function testRequestRouteWithNoArguments(Request $request): ?Route
    {
        return $request->route();
    }

    /** @phpstan-return string|object|null */
    public function testRequestRouteWithJustParam(Request $request): mixed
    {
        return $request->route('param');
    }

    /** @phpstan-return string|object|null */
    public function testRequestRouteWithParamAndDefaultValue(Request $request): mixed
    {
        return $request->route('param', 'default');
    }
}
