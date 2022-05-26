<?php

declare(strict_types=1);

namespace Route;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use function PHPStan\Testing\assertType;

assertType('Illuminate\Routing\Route', RouteFacade::get('/awesome', 'foobar')->middleware('foobar'));

function foo(Route $route)
{
    assertType('Illuminate\Routing\Route', $route->middleware('foobar'));
    assertType('array<string>', $route->middleware());
}
