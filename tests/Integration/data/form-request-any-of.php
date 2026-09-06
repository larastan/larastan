<?php

declare(strict_types=1);

namespace FormRequestAnyOfIntegration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use function PHPStan\Testing\assertType;

class ScalarAlternativesRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'value' => ['required', Rule::anyOf(['required|string', 'required|integer'])],
            'conditional' => ['required', Rule::when($this->boolean('flag'), 'string', 'integer')],
        ];
    }
}

function acceptsScalar(int|string $value): void
{
}

/** @param array<array-key, mixed> $value */
function acceptsArray(array $value): void
{
}

function test(ScalarAlternativesRequest $request): void
{
    $value = $request->validated('value');
    acceptsScalar($value);

    if (is_array($value)) {
        acceptsArray($value);
    }

    assertType('int|string', $request->conditional);
}
