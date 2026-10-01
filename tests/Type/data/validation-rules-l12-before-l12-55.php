<?php

declare(strict_types=1);

namespace ValidationRulesLaravel12Before12_55;

use App\Http\Requests\AdditionalRulesRequest;
use Illuminate\Validation\Rule;

use function PHPStan\Testing\assertType;

function test(AdditionalRulesRequest $request): void
{
    assertType(
        'Illuminate\\Validation\\Rules\\Numeric<float|int|numeric-string>',
        Rule::numeric()->integer()->min(1),
    );
    assertType(
        'Illuminate\\Validation\\Rules\\Numeric<float|int|numeric-string>',
        Rule::numeric()->integer()->min(1)->between(2, 8)->max(10),
    );
    assertType(
        'Illuminate\\Validation\\Rules\\Numeric<float|int|numeric-string>',
        Rule::numeric()->integer()->between(max: 8, min: 2),
    );
    assertType(
        'Illuminate\\Validation\\Rules\\Numeric<float|int|numeric-string>',
        Rule::numeric()->integer()->exactly(3),
    );
    assertType('float|int|numeric-string', $request->boundedNumericInteger);
}
