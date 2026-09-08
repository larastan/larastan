<?php

declare(strict_types=1);

namespace ValidationRulesLaravel12_55;

use App\Http\Requests\Laravel1255RulesRequest;
use Illuminate\Validation\Rule;

use function PHPStan\Testing\assertType;

function test(Laravel1255RulesRequest $request, bool $strict): void
{
    assertType('Illuminate\\Validation\\Rules\\StringRule<string>', Rule::string());
    assertType(
        'Illuminate\\Validation\\Rules\\StringRule<lowercase-string&non-empty-string>',
        Rule::string()->lowercase()->min(1)->max(20),
    );
    assertType('Illuminate\\Validation\\Rules\\StringRule<uppercase-string>', Rule::string()->uppercase());

    assertType('lowercase-string&non-empty-string', $request->lowercase);
    assertType('non-empty-string&uppercase-string', $request->uppercase);
    assertType('non-empty-string', $request->alpha);
    assertType('non-empty-string', $request->alwaysRequired);
    assertType('non-empty-string', $request->neverExcluded);
    assertType('mixed', $request->alwaysExcluded);

    assertType('Illuminate\\Validation\\Rules\\Numeric<int<1, max>>', Rule::numeric()->integer(strict: true)->min(1));
    assertType(
        'Illuminate\\Validation\\Rules\\Numeric<int<2, 8>>',
        Rule::numeric()->integer(strict: true)->min(1)->between(2, 8)->max(10),
    );
    assertType(
        'Illuminate\\Validation\\Rules\\Numeric<int<2, 8>>',
        Rule::numeric()->integer(strict: true)->between(max: 8, min: 2),
    );
    assertType('Illuminate\\Validation\\Rules\\Numeric<3>', Rule::numeric()->integer(strict: true)->exactly(3));
    assertType('int<2, 10>', $request->bounded);
    assertType('Illuminate\\Validation\\Rules\\Numeric<int>', Rule::numeric()->integer(strict: true)->digits(2));
    assertType('Illuminate\\Validation\\Rules\\Numeric<int>', Rule::numeric()->integer(strict: true)->digitsBetween(1, 2));
    assertType('int', $request->digits);
    assertType('int', $request->digitsBetween);
    assertType('Illuminate\\Validation\\Rules\\Numeric<float|int|numeric-string>', Rule::numeric()->integer(strict: false));
    assertType('Illuminate\\Validation\\Rules\\Numeric<float|int|numeric-string>', Rule::numeric()->integer(strict: $strict));
}
