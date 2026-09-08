<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class OptionalRulesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'value' => $this->valueRules(),
            'payload' => $this->payloadRules(),
            'payload.name' => 'required|string',
            'stable' => 'required|string',
            'conditionalNullable' => ['present', 'string', Rule::when(true, $this->nullableRules())],
        ];
    }

    /** @return array{0: 'required', 1?: 'string'} */
    private function valueRules(): array
    {
        return $this->boolean('restrict') ? ['required', 'string'] : ['required'];
    }

    /** @return array{0: 'array', 1?: 'exclude'} */
    private function payloadRules(): array
    {
        return $this->boolean('exclude') ? ['array', 'exclude'] : ['array'];
    }

    /** @return array{0?: 'nullable'} */
    private function nullableRules(): array
    {
        return $this->boolean('nullable') ? ['nullable'] : [];
    }
}
