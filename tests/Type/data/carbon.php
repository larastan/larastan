<?php

namespace Carbon;

use function PHPStan\Testing\assertType;

function testCarbonMacroCalledStatically()
{
    assertType('string', Carbon::foo());
}

function testCarbonMacroCalledDynamically()
{
    assertType('string', Carbon::now()->foo());
}
