<?php

namespace MacrosRouteRegistrar;

use Illuminate\Routing\RouteRegistrar;

use function PHPStan\Testing\assertType;

/** RouteRegistrar uses Macroable but defines its own __call. */
function test(RouteRegistrar $routeRegistrar): void
{
    assertType('Illuminate\Routing\RouteRegistrar', $routeRegistrar->routeRegistrarMacro());
}
