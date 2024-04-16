<?php

declare(strict_types=1);

namespace ContainerArrayAccess;

use Illuminate\Container\Container;

use function PHPStan\Testing\assertType;

/** @param 'auth'|'translator' $arg */
function test(Container $app, string $arg): void
{
    assertType('Illuminate\Auth\AuthManager', $app['auth']);
    assertType('Illuminate\Translation\Translator', $app['translator']);
    assertType('Illuminate\Auth\AuthManager|Illuminate\Translation\Translator', $app[$arg]);
}
