<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SelectorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'numeric' => 'required|array:0,1',
            'negative.-1.name' => 'required|string',
            'zero.0.name' => 'required|string',
            'leadingZero.01.name' => 'required|string',
            'stringPlus.+1.name' => 'required|string',
            'stringNegativeZero.-0.name' => 'required|string',
            'stringNegativeLeadingZero.-01.name' => 'required|string',
            'profile.first' => 'required|string',
            'profile.last' => 'required|string',
            'literal{first}name' => 'required|string',
            'profile.{first}' => 'required|string',
            'profile.{last}' => 'required|string',
        ];
    }
}
