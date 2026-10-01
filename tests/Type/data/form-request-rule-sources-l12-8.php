<?php

declare(strict_types=1);

namespace FormRequestRuleSourcesL12_8;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


use function PHPStan\Testing\assertType;

function testDifferentAnyOfReturns(DifferentAnyOfReturnsRequest $different): void
{
    assertType('array{payload: (array|non-empty-string)}', $different->validated());
}

class DifferentAnyOfReturnsRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->isMethod('POST')) {
            return ['payload' => ['required', Rule::anyOf(['required|string'])]];
        }

        return ['payload' => ['required', Rule::anyOf(['string'])]];
    }
}
