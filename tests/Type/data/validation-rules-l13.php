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

function test(ArrayKeysRequest $request): void
{
    assertType(
        "Illuminate\\Validation\\Rules\\ArrayKeys<array{'name', 'email'}>",
        Rule::arrayKeys(['name', 'email']),
    );
    assertType('array{name: string, email?: mixed}', $request->payload);
    assertType('array{name: string, email?: mixed}', $request->validated('payload'));
    assertType('array{name: string, email?: mixed}', $request->validated('stringPayload'));
    assertType('array{name?: string, email?: mixed}', $request->validated('optionalChild'));
    assertType('array{name?: string}|null', $request->validated('pruned'));
    assertType('array{0?: mixed, 1?: mixed}', $request->validated('numeric'));
    assertType('array', $request->tags);
    assertType('array', $request->filteredTags);
}
