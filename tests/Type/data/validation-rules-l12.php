<?php

declare(strict_types=1);

namespace ValidationRulesLaravel12;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use function PHPStan\Testing\assertType;

final class StringRuleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'lowercase' => ['required', Rule::string()->lowercase()->max(20)],
            'uppercase' => ['required', Rule::string()->uppercase()],
            'alpha' => ['required', Rule::string()->alpha(ascii: true)],
        ];
    }
}

function test(StringRuleRequest $request, bool $strict): void
{
    assertType('Illuminate\\Validation\\Rules\\StringRule<string>', Rule::string());
    assertType(
        'Illuminate\\Validation\\Rules\\StringRule<lowercase-string>',
        Rule::string()->lowercase()->max(20),
    );
    assertType('Illuminate\\Validation\\Rules\\StringRule<uppercase-string>', Rule::string()->uppercase());
    assertType('Illuminate\\Validation\\Rules\\Numeric<int>', Rule::numeric()->integer(strict: true)->min(1));
    assertType('Illuminate\\Validation\\Rules\\Numeric<(int|numeric-string)>', Rule::numeric()->integer(strict: false));
    assertType('Illuminate\\Validation\\Rules\\Numeric<(int|numeric-string)>', Rule::numeric()->integer(strict: $strict));
    assertType('Illuminate\\Validation\\Rules\\Date<float|int|string>', Rule::dateTime());

    assertType('lowercase-string', $request->lowercase);
    assertType('uppercase-string', $request->uppercase);
    assertType('string', $request->alpha);
}
