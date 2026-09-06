<?php

declare(strict_types=1);

namespace ValidationRulesLaravel12;

use Illuminate\Validation\Rule;

use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('Illuminate\\Validation\\Rules\\Numeric<(float|int|numeric-string)>', Rule::numeric()->integer());
}
