<?php

declare(strict_types=1);

namespace FormRequestAllowedKeys;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use function PHPStan\Testing\assertType;

final class AllowedKeysRequest extends FormRequest
{
    /** @var list<string> */
    private array $keys = ['name'];

    /** @var non-empty-list<string> */
    private array $nonEmptyKeys = ['name', 'other', 'kept'];

    public function rules(): array
    {
        return [
            'object' => ['required', Rule::array(['name', 'other'])],
            'object.name' => 'string',
            'string' => 'required|array:name,other',
            'string.name' => 'string',
            'requiredChild' => 'required|array:name,other',
            'requiredChild.name' => 'required|string',
            'nested' => ['required', Rule::array(['name'])],
            'nested.name' => 'array',
            'nested.name.first' => 'required|string',
            'requiredNested' => 'required|array:name',
            'requiredNested.name' => 'required|array',
            'requiredNested.name.first' => 'string',
            'nestedAllowed' => 'required|array:name',
            'nestedAllowed.name' => 'array:first,other',
            'nestedAllowed.name.first' => 'required|string',
            'pruned' => ['required', 'array', Rule::array(['name', 'other'])],
            'pruned.name' => 'string',
            'excluded' => ['required', Rule::array(['name', 'other'])],
            'excluded.name' => 'exclude',
            'conditional' => 'required|array:name,other',
            'conditional.name' => 'exclude_if:flag,true|string',
            'lastExcluded' => ['required', 'array', Rule::array(['name', 'other'])],
            'lastExcluded.name' => 'exclude',
            'partialExcluded' => ['required', 'array', Rule::array(['name', 'other', 'missing'])],
            'partialExcluded.name' => 'exclude',
            'partialExcluded.missing' => 'string',
            'conditionalPruning' => ['required', Rule::array(['name', 'other']), Rule::when($this->boolean('flag'), 'array')],
            'conditionalPruning.name' => 'string',
            'dynamicPruning' => ['required', 'array:name,other', Rule::array($this->keys)],
            'dynamicPruning.name' => 'string',
            'emptyPruning' => ['required', 'array:name,other', Rule::array([])],
            'emptyPruning.name' => 'string',
            'literalPruning' => ['required', 'array', 'array:name,other', Rule::array($this->keys)],
            'literalPruning.name' => 'string',
            'unknownKeys' => ['required', Rule::array($this->keys)],
            'unknownKeys.name' => 'string',
            'nonEmptyKeys' => ['required', Rule::array($this->nonEmptyKeys)],
            'nonEmptyKeys.name' => 'string',
            'unknownNested' => ['required', Rule::array($this->nonEmptyKeys)],
            'unknownNested.name' => 'array',
            'unknownNested.name.first' => 'required|string',
            'unknownExcluded' => ['required', Rule::array($this->nonEmptyKeys)],
            'unknownExcluded.name' => 'exclude',
            'unknownExcluded.kept' => 'required|string',
            'numeric' => 'required|array:0,1',
            'quoted' => 'required|array:"first,last"',
            'serialized' => ['required', Rule::array(['first,last'])],
        ];
    }
}

function testAllowedKeys(AllowedKeysRequest $request): void
{
    assertType('array{name?: string, other?: mixed}', $request->validated('object'));
    assertType('array{name?: string, other?: mixed}', $request->validated('string'));
    assertType('array{name: string, other?: mixed}', $request->validated('requiredChild'));
    assertType('array{name: array{first: string, ...}}', $request->validated('nested'));
    assertType('array{name: array{first?: string, ...}}', $request->validated('requiredNested'));
    assertType('array{name: array{first: string, other?: mixed}}', $request->validated('nestedAllowed'));
    assertType('array{name?: string}|null', $request->validated('pruned'));
    assertType('array{name?: string, other?: mixed}', $request->pruned);
    assertType('array{other?: mixed}', $request->validated('excluded'));
    assertType('null', $request->validated('excluded.name'));
    assertType('array{name?: string, other?: mixed}', $request->validated('conditional'));
    assertType('array{other?: mixed}', $request->validated('lastExcluded'));
    assertType('array{missing?: string}|null', $request->validated('partialExcluded'));
    assertType('array{name?: string, other?: mixed}|null', $request->validated('conditionalPruning'));
    assertType('array{name?: string, other?: mixed}|null', $request->validated('dynamicPruning'));
    assertType('array{name?: string}|null', $request->validated('emptyPruning'));
    assertType('array{name?: string}|null', $request->validated('literalPruning'));
    assertType('array{name?: string, ...}|null', $request->validated('unknownKeys'));
    assertType('array{name?: string, ...}', $request->validated('nonEmptyKeys'));
    assertType('array{name: array{first: string, ...}, ...}', $request->validated('unknownNested'));
    assertType('array{kept: string, ...}', $request->validated('unknownExcluded'));
    assertType('array{0?: mixed, 1?: mixed}', $request->validated('numeric'));
    assertType("array{'first,last'?: mixed}", $request->validated('quoted'));
    assertType('array{first?: mixed, last?: mixed}', $request->validated('serialized'));
}
