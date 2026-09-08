<?php

declare(strict_types=1);

namespace FormRequestRuleSourcesL12_8;

use App\Http\Requests\DifferentAnyOfReturnsRequest;
use App\Http\Requests\EquivalentAnyOfReturnsRequest;

use function PHPStan\Testing\assertType;

function testEquivalentRules(EquivalentAnyOfReturnsRequest $equivalent, DifferentAnyOfReturnsRequest $different): void
{
    assertType('array{payload: (0|1|array|bool|non-empty-string)}', $equivalent->validated());
    assertType('array', $different->validated());
}
