<?php

declare(strict_types=1);

namespace ValidationRulesLaravel12_44;

use Illuminate\Validation\Rule;

use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('Illuminate\\Validation\\Rules\\Date<float|int|string>', Rule::dateTime());
}
