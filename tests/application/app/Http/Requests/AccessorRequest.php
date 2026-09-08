<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccessorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'nickname' => 'string',
            'terms' => 'required|accepted',
            'marketing' => 'declined',
            'active' => 'required|boolean',
            'count' => 'required|integer|between:1,5',
            'amount' => 'required|numeric|min:1',
            'digits' => 'required|digits:4',
            'mode' => 'required|in:a,b',
            'avatar' => ['required', Rule::file()],
            'photos' => 'array',
            'photos.*' => [Rule::imageFile()],
            'profile.age' => 'required|integer|max:120',
            'profile.city' => 'string',
            'tags' => 'sometimes|array',
            'tags.*' => 'string',
        ];
    }
}
