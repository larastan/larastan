<?php

namespace ConfigFileScope;

use function PHPStan\Testing\assertType;

function phpDocUsesConfigFileImports(): void
{
    assertType('Illuminate\Support\Collection<int, string>', config('scopedoc.items'));
}
