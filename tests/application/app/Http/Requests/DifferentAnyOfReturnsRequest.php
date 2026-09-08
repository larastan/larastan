<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
