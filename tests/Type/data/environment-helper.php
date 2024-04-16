<?php

namespace EnvironmentHelper;

use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('string', app()->environment());
    assertType('bool', app()->environment('local'));
    assertType('bool', app()->environment(['local', 'production']));
}
