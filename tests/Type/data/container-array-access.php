<?php

declare(strict_types=1);

namespace ContainerArrayAccess;

use function PHPStan\Testing\assertType;

/** @var \Illuminate\Container\Container $app */
assertType('Illuminate\Auth\AuthManager', $app['auth']);
assertType('Illuminate\Translation\Translator', $app['translator']);

/** @var 'auth'|'translator' $args */
assertType('Illuminate\Auth\AuthManager|Illuminate\Translation\Translator', $app[$args]);
