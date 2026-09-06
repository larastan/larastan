<?php

declare(strict_types=1);

namespace ValidationRulesLooseIn;

use ValidationRules\AdditionalRulesRequest;

use function PHPStan\Testing\assertType;

function test(AdditionalRulesRequest $request): void
{
    assertType('numeric-string', $request->stringNumericInValue);
    assertType("'draft'|numeric-string", $request->stringMixedInValue);
}
