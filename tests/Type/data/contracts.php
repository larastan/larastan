<?php

namespace Contracts;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Session\Session;

use function PHPStan\Testing\assertType;

function test(Application $app, Session $session): void
{
    assertType('bool', $app->isLocal());
    assertType('null', $session->ageFlashData());
}
