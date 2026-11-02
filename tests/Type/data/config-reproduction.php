<?php

namespace ConfigReproduction;

use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('array{key: string}', config('reproduction.nested'));
    assertType('string', config('reproduction.nested.key'));
}
