<?php

namespace FormRequestRuleIntegration;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'email' => 'required|string',
            'advisory' => 'required|nullable',
            'impossible' => 'required|missing|nullable',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->validated('emali', false);
        // @phpstan-ignore larastan.formRequest.unknownValidatedKey
        $this->validated('another_typo');
    }
}

class UpdateRequest extends FormRequest
{
    /** @return array<string, string> */
    public function rules(): array
    {
        return ['name' => 'string'];
    }
}

function unionKey(StoreRequest|UpdateRequest $request): void
{
    $request->validated('email');
}
