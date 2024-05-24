<?php

namespace MacrosPHP81;

use Illuminate\Database\Eloquent\Builder;

use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('string', Builder::macroWithEnumDefaultValue());
}
