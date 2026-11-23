<?php

declare(strict_types=1);

namespace ValidationRulesLaravel12_55;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use function PHPStan\Testing\assertType;

function test(Laravel1255RulesRequest $request): void
{
    assertType('Illuminate\\Validation\\Rules\\StringRule<string>', Rule::string());

    assertType('non-empty-string', $request->alwaysRequired);
    assertType('non-empty-string', $request->neverExcluded);
    assertType('mixed', $request->alwaysExcluded);

    assertType(
        'Illuminate\\Validation\\Rules\\Numeric<int<2, 8>>',
        Rule::numeric()->integer(strict: true)->between(max: 8, min: 2),
    );
    assertType('Illuminate\\Validation\\Rules\\Numeric<3>', Rule::numeric()->integer(strict: true)->exactly(3));
    assertType('int<2, 10>', $request->bounded);
}

class Laravel1255RulesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'bounded' => ['required', Rule::numeric()->integer(strict: true)->max(10)->min(2)],
            'alwaysRequired' => [Rule::requiredUnless(false), 'string'],
            'neverExcluded' => ['required', Rule::excludeUnless(true), 'string'],
            'alwaysExcluded' => ['required', Rule::excludeUnless(false), 'string'],
        ];
    }
}
