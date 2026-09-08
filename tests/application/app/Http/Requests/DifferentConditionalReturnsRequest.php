<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DifferentConditionalReturnsRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->isMethod('POST')) {
            return ['payload' => ['required', Rule::when(true, ['string', 'exclude'])]];
        }

        return ['payload' => ['required', Rule::when(true, ['string'])]];
    }
}
