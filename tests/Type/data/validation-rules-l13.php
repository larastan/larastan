<?php

declare(strict_types=1);

namespace ValidationRulesLaravel13;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use function PHPStan\Testing\assertType;

final class ArrayKeysRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'payload' => ['required', Rule::arrayKeys(['name', 'email'])],
            'payload.name' => ['required', 'string'],
            'tags' => ['required', Rule::contains(['php', 'laravel'])],
        ];
    }
}

function test(ArrayKeysRequest $request): void
{
    assertType(
        "Illuminate\\Validation\\Rules\\ArrayKeys<array{'name', 'email'}>",
        Rule::arrayKeys(['name', 'email']),
    );
    assertType('array{name: string, email?: mixed}', $request->payload);
    assertType('array', $request->tags);
}
