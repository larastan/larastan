<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ArrayKeysRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'payload' => ['required', Rule::arrayKeys(['name', 'email'])],
            'payload.name' => ['required', 'string'],
            'stringPayload' => 'required|array_keys:name,email',
            'stringPayload.name' => ['required', 'string'],
            'optionalChild' => 'required|array_keys:name,email',
            'optionalChild.name' => 'string',
            'pruned' => 'required|array|array_keys:name,email',
            'pruned.name' => 'string',
            'numeric' => 'required|array_keys:0,1',
            'tags' => ['required', Rule::contains(['php', 'laravel'])],
            'filteredTags' => ['required', Rule::doesntContain(['deprecated'])],
        ];
    }
}
