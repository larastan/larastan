<?php

declare(strict_types=1);

namespace ValidationRulesLaravel12_55;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use function PHPStan\Testing\assertType;

final class StringRuleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'lowercase' => ['required', Rule::string()->lowercase()->min(1)->max(20)],
            'uppercase' => ['required', Rule::string()->uppercase()],
            'alpha' => ['required', Rule::string()->alpha(ascii: true)],
        ];
    }
}

final class UnlessRulesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'alwaysRequired' => [Rule::requiredUnless(false), 'string'],
            'neverExcluded' => ['required', Rule::excludeUnless(true), 'string'],
            'alwaysExcluded' => ['required', Rule::excludeUnless(false), 'string'],
        ];
    }
}

final class StrictNumericRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'bounded' => ['required', Rule::numeric()->integer(strict: true)->max(10)->min(2)],
            'digits' => ['required', Rule::numeric()->integer(strict: true)->digits(2)],
            'digitsBetween' => ['required', Rule::numeric()->integer(strict: true)->digitsBetween(1, 2)],
        ];
    }
}

function test(
    StringRuleRequest $request,
    UnlessRulesRequest $unlessRequest,
    StrictNumericRequest $numericRequest,
    bool $strict,
): void
{
    assertType('Illuminate\\Validation\\Rules\\StringRule<string>', Rule::string());
    assertType(
        'Illuminate\\Validation\\Rules\\StringRule<lowercase-string&non-empty-string>',
        Rule::string()->lowercase()->min(1)->max(20),
    );
    assertType('Illuminate\\Validation\\Rules\\StringRule<uppercase-string>', Rule::string()->uppercase());

    assertType('lowercase-string&non-empty-string', $request->lowercase);
    assertType('uppercase-string', $request->uppercase);
    assertType('string', $request->alpha);
    assertType('string', $unlessRequest->alwaysRequired);
    assertType('string', $unlessRequest->neverExcluded);
    assertType('mixed', $unlessRequest->alwaysExcluded);

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
    assertType('int<2, 10>', $numericRequest->bounded);
    assertType('Illuminate\\Validation\\Rules\\Numeric<int>', Rule::numeric()->integer(strict: true)->digits(2));
    assertType('Illuminate\\Validation\\Rules\\Numeric<int>', Rule::numeric()->integer(strict: true)->digitsBetween(1, 2));
    assertType('int', $numericRequest->digits);
    assertType('int', $numericRequest->digitsBetween);
    assertType('Illuminate\\Validation\\Rules\\Numeric<(float|int|numeric-string)>', Rule::numeric()->integer(strict: false));
    assertType('Illuminate\\Validation\\Rules\\Numeric<(float|int|numeric-string)>', Rule::numeric()->integer(strict: $strict));
}
