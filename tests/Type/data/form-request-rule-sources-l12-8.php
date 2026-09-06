<?php

declare(strict_types=1);

namespace FormRequestRuleSourcesL12_8;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use function PHPStan\Testing\assertType;

class EquivalentAnyOfReturnsRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->isMethod('POST')) {
            return ['payload' => ['required', Rule::anyOf(['required|string', 'required|boolean'])]];
        }

        return ['payload' => ['required', Rule::anyOf(['required|string', 'required|boolean'])]];
    }
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

function testEquivalentRules(EquivalentAnyOfReturnsRequest $equivalent, DifferentAnyOfReturnsRequest $different): void
{
    assertType('array{payload: (0|1|array|bool|string)}', $equivalent->validated());
    assertType('array', $different->validated());
}
