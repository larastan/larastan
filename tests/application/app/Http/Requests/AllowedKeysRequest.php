<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'payload' => 'required|array',
            'payload.name' => 'exclude',
            'excludedConditional' => 'required|array',
            'excludedConditional.name' => 'exclude_if:flag,true|string',
            'sibling' => 'required|array',
            'sibling.name' => 'exclude',
            'sibling.missing' => 'string',
            'deepSibling' => 'required|array',
            'deepSibling.meta.name' => 'exclude',
            'deepSibling.meta.missing' => 'string',
            'subtree' => 'required|array',
            'subtree.meta' => 'exclude|array',
            'subtree.meta.name' => 'required|string',
            'excludedNested' => 'required|array',
            'excludedNested.meta.name' => 'exclude',
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
