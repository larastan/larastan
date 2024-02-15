<?php

namespace Carbon;

use function PHPStan\Testing\assertType;

function testCarbonMacroCalledStatically()
{
    assertType('string', Carbon::customCarbonMacro());
}

function testCarbonMacroCalledDynamically()
{
    assertType('string', Carbon::now()->customCarbonMacro());
}

function testCarbonMixinCalledStatically()
{
    assertType('int', Carbon::customCarbonMixinStatic());
}

function testCarbonMixinCalledDynamically()
{
    assertType('bool', Carbon::now()->customCarbonMixinInstance());
}
