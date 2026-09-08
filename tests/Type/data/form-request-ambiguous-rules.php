<?php

declare(strict_types=1);

namespace FormRequestAmbiguousRules;

use App\Http\Requests\FooRequest;
use App\Http\Requests\OptionalRulesRequest;

use function PHPStan\Testing\assertType;

function test(OptionalRulesRequest $optional, FooRequest $enum): void
{
    assertType('mixed', $optional->value);
    assertType('mixed', $optional->payload);
    assertType('array{value?: mixed, payload?: mixed, stable: non-empty-string, conditionalNullable?: mixed}', $optional->validated());
    assertType('mixed', $optional->conditionalNullable);
    assertType('mixed', $optional->validated('payload.name'));
    assertType('(1|2|numeric-string)', $enum->priority);
    assertType('numeric-string', $enum->stringPriority);
    assertType('(1|2|numeric-string)', $enum->validated('priority'));

    if ($enum->validated('priority') === '01') {
        assertType("'01'", $enum->validated('priority'));
    }
}
