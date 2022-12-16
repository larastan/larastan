<?php

namespace Contracts;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Session\Session;
use function PHPStan\Testing\assertType;

function testApplicationIsLocal(Application $app)
{
    assertType('bool', $app->isLocal());
}

function testSessionAgeFlashData(Session $session)
{
    assertType('void', $session->ageFlashData());
}
