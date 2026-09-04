<?php

declare(strict_types=1);

namespace ValidationRulesLaravel12;

use Illuminate\Validation\Rule;

use function PHPStan\Testing\assertType;

function test(bool $strict): void
{
    assertType('Illuminate\\Validation\\Rules\\Numeric<int>', Rule::numeric()->integer(strict: true)->min(1));
    assertType('Illuminate\\Validation\\Rules\\Numeric<(int|numeric-string)>', Rule::numeric()->integer(strict: false));
    assertType('Illuminate\\Validation\\Rules\\Numeric<(int|numeric-string)>', Rule::numeric()->integer(strict: $strict));
}
