<?php

declare(strict_types=1);

namespace FormRequestExclusion;

use Illuminate\Foundation\Http\FormRequest;

use function PHPStan\Testing\assertType;

class ExcludedChildrenRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'payload' => 'required|array',
            'payload.name' => 'exclude',
            'conditional' => 'required|array',
            'conditional.name' => 'exclude_if:flag,true|string',
            'sibling' => 'required|array',
            'sibling.name' => 'exclude',
            'sibling.missing' => 'string',
            'deepSibling' => 'required|array',
            'deepSibling.meta.name' => 'exclude',
            'deepSibling.meta.missing' => 'string',
            'subtree' => 'required|array',
            'subtree.meta' => 'exclude|array',
            'subtree.meta.name' => 'required|string',
            'nested' => 'required|array',
            'nested.meta.name' => 'exclude',
            'elements' => 'required|array',
            'elements.*' => 'exclude',
            'items' => 'required|array',
            'items.*.name' => 'exclude',
            'arrays' => 'required|array',
            'arrays.*' => 'array',
            'arrays.*.name' => 'exclude',
            'unruled.name' => 'exclude',
        ];
    }
}

function testExcludedChildren(ExcludedChildrenRequest $request): void
{
    assertType('array', $request->validated('payload'));
    assertType('array{name?: string, ...}|null', $request->validated('conditional'));
    assertType('array{missing?: string}|null', $request->validated('sibling'));
    assertType('null', $request->validated('sibling.name'));
    assertType('array{meta?: array{missing?: string}}|null', $request->validated('deepSibling'));
    assertType('array', $request->validated('subtree'));
    assertType('array{meta?: mixed, ...}', $request->validated('nested'));
    assertType('array{}', $request->validated('elements'));
    assertType('array', $request->validated('items'));
    assertType('array<array>|null', $request->validated('arrays'));
    assertType('array{}|null', $request->validated('unruled'));
}
