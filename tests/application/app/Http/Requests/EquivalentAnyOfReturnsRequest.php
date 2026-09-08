<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
