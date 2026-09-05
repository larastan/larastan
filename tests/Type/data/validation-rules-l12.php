<?php

declare(strict_types=1);

namespace ValidationRulesLaravel12;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use function PHPStan\Testing\assertType;

final class StrictNumericRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'bounded' => ['required', Rule::numeric()->integer(strict: true)->max(10)->min(2)],
        ];
    }
}

function test(bool $strict, StrictNumericRequest $request): void
{
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
    assertType('Illuminate\\Validation\\Rules\\Numeric<(int|numeric-string)>', Rule::numeric()->integer(strict: false));
    assertType('Illuminate\\Validation\\Rules\\Numeric<(int|numeric-string)>', Rule::numeric()->integer(strict: $strict));
    assertType('int<2, 10>', $request->bounded);
}
