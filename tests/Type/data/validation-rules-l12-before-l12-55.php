<?php

declare(strict_types=1);

namespace ValidationRulesLaravel12Before12_55;

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

function test(StrictNumericRequest $request): void
{
    assertType(
        'Illuminate\\Validation\\Rules\\Numeric<(int|numeric-string)>',
        Rule::numeric()->integer(strict: true)->min(1),
    );
    assertType(
        'Illuminate\\Validation\\Rules\\Numeric<(int|numeric-string)>',
        Rule::numeric()->integer(strict: true)->min(1)->between(2, 8)->max(10),
    );
    assertType(
        'Illuminate\\Validation\\Rules\\Numeric<(int|numeric-string)>',
        Rule::numeric()->integer(strict: true)->between(max: 8, min: 2),
    );
    assertType(
        'Illuminate\\Validation\\Rules\\Numeric<(int|numeric-string)>',
        Rule::numeric()->integer(strict: true)->exactly(3),
    );
    assertType('(int|numeric-string)', $request->bounded);
}
