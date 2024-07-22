<?php

namespace Helpers;

use Illuminate\Support\Facades\Config;

use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('array{guard: string, passwords: string}', Config::array('auth.defaults'));
}
