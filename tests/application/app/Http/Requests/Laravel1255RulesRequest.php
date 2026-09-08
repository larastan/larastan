<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class Laravel1255RulesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'bounded' => ['required', Rule::numeric()->integer(strict: true)->max(10)->min(2)],
            'digits' => ['required', Rule::numeric()->integer(strict: true)->digits(2)],
            'digitsBetween' => ['required', Rule::numeric()->integer(strict: true)->digitsBetween(1, 2)],
            'lowercase' => ['required', Rule::string()->lowercase()->min(1)->max(20)],
            'uppercase' => ['required', Rule::string()->uppercase()],
            'alpha' => ['required', Rule::string()->alpha(ascii: true)],
            'alwaysRequired' => [Rule::requiredUnless(false), 'string'],
            'neverExcluded' => ['required', Rule::excludeUnless(true), 'string'],
            'alwaysExcluded' => ['required', Rule::excludeUnless(false), 'string'],
        ];
    }
}
