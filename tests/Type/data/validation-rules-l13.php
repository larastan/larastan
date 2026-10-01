<?php

declare(strict_types=1);

namespace ValidationRulesLaravel13;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


use function PHPStan\Testing\assertType;

function test(ArrayKeysRequest $request): void
{
    assertType('array{name: non-empty-string, email?: mixed}', $request->payload);
    assertType('array{0?: mixed, 1?: mixed}', $request->validated('numeric'));
    assertType('array', $request->tags);
    assertType('array', $request->filteredTags);
}

class ArrayKeysRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'payload' => ['required', Rule::arrayKeys(['name', 'email'])],
            'payload.name' => ['required', 'string'],
            'pruned' => 'required|array|array_keys:name,email',
            'pruned.name' => 'string',
            'numeric' => 'required|array_keys:0,1',
            'tags' => ['required', Rule::contains(['php', 'laravel'])],
            'filteredTags' => ['required', Rule::doesntContain(['deprecated'])],
        ];
    }
}
