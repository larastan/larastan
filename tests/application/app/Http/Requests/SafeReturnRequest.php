<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SafeReturnRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'nickname' => 'string',
            'profile.email' => 'required|string',
            'profile.age' => 'integer',
            'excluded' => 'exclude',
            'unknown' => 'required',
        ];
    }
}
