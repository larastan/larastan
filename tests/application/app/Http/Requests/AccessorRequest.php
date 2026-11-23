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
            'marketing' => 'declined',
            'active' => 'required|boolean',
            'count' => 'required|integer|between:1,5',
            'mode' => 'required|in:a,b',
            'status' => ['required', Rule::enum(RequestStatus::class)],
            'priority' => ['required', Rule::enum(RequestPriority::class)],
            'role' => ['required', Rule::enum(RequestRole::class)],
            'avatar' => ['required', Rule::file()],
            'profile.age' => 'required|integer|max:120',
            'profile.city' => 'string',
            'tags' => 'sometimes|array',
            'tags.*' => 'string',
        ];
    }
}
