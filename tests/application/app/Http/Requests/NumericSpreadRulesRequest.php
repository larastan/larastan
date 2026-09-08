<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NumericSpreadRulesRequest extends FormRequest
{
    /** @return array<int, string> */
    private function additionalRules(): array
    {
        return [0 => 'exclude'];
    }

    /** @return array<'other', string> */
    private function unrelatedRules(): array
    {
        return ['other' => 'exclude'];
    }

    /** @return array{email: array{'required', 'email'}} */
    private function commonRules(): array
    {
        return ['email' => ['required', 'email']];
    }

    public function rules(): array
    {
        return [
            ...$this->unrelatedRules(),
            'before' => 'required|string',
            ...$this->additionalRules(),
            'parent.name' => 'required|string',
            ...$this->commonRules(),
        ];
    }
}
