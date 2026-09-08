<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IntegerKeyRulesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            0 => 'required|string',
            '1' => 'required|integer',
        ];
    }
}
