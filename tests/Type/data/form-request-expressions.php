<?php

declare(strict_types=1);

namespace FormRequestExpressions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use function is_callable;
use function PHPStan\Testing\assertType;

function testReassignedRules(ReassignedRulesRequest $reassigned, OffsetAssignedRulesRequest $offsetAssigned): void
{
    assertType('float|int|non-empty-string', $reassigned->value);
    assertType('float|int|numeric-string', $offsetAssigned->value);
}

function testFirstClassCallables(DeclaredPropertiesRequest $request): void
{
    assertType('true', is_callable($request->validated(...)));
    assertType('true', is_callable($request->input(...)));
    assertType('true', is_callable(Rule::in(...)));
    assertType('true', is_callable($request->safe()->only(...)));
}

function testDeclaredProperties(DeclaredPropertiesRequest $request): void
{
    assertType('mixed', $request->documented);
    assertType('mixed', $request->nativeName);
}

function testAccessForms(DeclaredPropertiesRequest $request, DeclaredPropertiesRequest|null $nullable): void
{
    assertType('non-empty-string|null', $nullable?->name);
    assertType('non-empty-string|null', $nullable?->input('name'));
    assertType('non-empty-string', $request->{'name'});
    assertType('non-empty-string', $request->{'input'}('name'));

    if ($request->nickname === null) {
        return;
    }

    assertType('string', $request->nickname);
}

function testValidatedListOrder(ListElementRulesRequest $request): void
{
    assertType('list<string>|null', $request->validated('scalars'));
    assertType('array<int, array>|null', $request->validated('arrays'));
    assertType('array<int, mixed>|null', $request->validated('any'));
    assertType('list<array{id: float|int|numeric-string}>|null', $request->validated('rows'));
    assertType('list<array>', $request->arrays);
    assertType('array<int, string>|null', $request->validated('skippable'));
}

function testAllowedKeysWithWildcardRules(ListElementRulesRequest $request): void
{
    assertType('array{theme?: string, locale?: string}', $request->validated('options'));
    assertType("array{beta?: 1|'1'|'on'|'true'|'yes'|true}|null", $request->validated('flags'));
}

/** @property mixed $documented */
class DeclaredPropertiesRequest extends FormRequest
{
    public mixed $nativeName;

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'nickname' => 'string',
            'documented' => 'required|string',
            'nativeName' => 'required|string',
        ];
    }
}

class ListElementRulesRequest extends FormRequest
{
    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'scalars' => 'required|list',
            'scalars.*' => 'string',
            'arrays' => 'required|list',
            'arrays.*' => 'array',
            'any' => 'required|list',
            'any.*' => 'nullable',
            'skippable' => 'required|list',
            'skippable.*' => 'exclude_if:skip,1|string',
            'rows' => 'required|list',
            'rows.*.id' => 'required|integer',
            'options' => 'required|array:theme,locale',
            'options.*' => 'string',
            'flags' => 'required|array|array:beta',
            'flags.*' => 'accepted',
        ];
    }
}

class ReassignedRulesRequest extends FormRequest
{
    /** @return array<string, string> */
    public function rules(): array
    {
        $rules = ['value' => 'required|string'];

        if ($this->boolean('numeric')) {
            $rules = ['value' => 'required|integer'];
        }

        return $rules;
    }
}

class OffsetAssignedRulesRequest extends FormRequest
{
    /** @return array<string, string> */
    public function rules(): array
    {
        $rules          = ['value' => 'required|string'];
        $rules['value'] = 'required|integer';

        return $rules;
    }
}
