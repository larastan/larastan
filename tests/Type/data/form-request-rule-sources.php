<?php

declare(strict_types=1);

namespace FormRequestRuleSources;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use FormRequestLifecycle\ExactRulesRequest;
use FormRequestLifecycle\ParentCompositionRequest;

use function PHPStan\Testing\assertType;

function testRuleSources(
    ExactRulesRequest $exact,
    UnpackedRulesRequest $unpacked,
    UnknownAncestorKeyRequest $unknownAncestorKey,
    OptionalAncestorRulesRequest $optionalAncestor,
    RootWildcardRulesRequest $rootWildcard,
    MultipleReturnsRequest $multiple,
    DifferentArrayReturnsRequest $differentArrays,
    DifferentConditionalReturnsRequest $differentConditions,
    NestedReturnsRequest $nested,
    ParentCompositionRequest $parentComposition,
    BroadPhpDocDirectRequest $broadPhpDocDirect,
    LoopBuiltRulesRequest $loopBuilt,
    IntegerKeyRulesRequest $integerKeys,
): void {
    assertType('mixed', $exact->unrelated);

    assertType('mixed', $unpacked->dynamicOnly);

    assertType('array{stable: non-empty-string, ...}', $unknownAncestorKey->validated());
    assertType('array{stable: non-empty-string, ...}', $optionalAncestor->validated());
    assertType('mixed', $multiple->parent);

    assertType('mixed', $rootWildcard->{'*'});
    assertType('array<string, mixed>', $rootWildcard->safe(['0.name']));

    assertType('mixed', $multiple->firstOnly);
    assertType('mixed', $multiple->secondOnly);

    assertType('array', $differentArrays->validated());
    assertType('array{payload?: mixed}', $differentConditions->validated());

    assertType('non-empty-string', $nested->actual);
    assertType('mixed', $nested->closure);

    assertType('mixed', $parentComposition->exact);
    assertType('mixed', $parentComposition->composed);
    assertType('mixed', $broadPhpDocDirect->anything);

    assertType('mixed', $loopBuilt->anything);

    assertType('array{shared: non-empty-string, different: float|int|non-empty-string, payload: array{name?: mixed}, record: array{name: non-empty-string}, pruned?: array{name?: string, other?: mixed}, ...}', $multiple->validated());

    assertType('mixed', $integerKeys->{'0'});
    assertType('mixed', $integerKeys->{'1'});
    assertType('array', $integerKeys->validated());
}

class UnpackedRulesRequest extends FormRequest
{
    private const BEFORE = ['spreadOverwritten' => 'required|string'];

    private const AFTER = ['constant' => 'required|string'];

    /** @return array<string, string> */
    private function dynamicRules(): array
    {
        return ['dynamicOnly' => 'required|string', 'parent' => 'exclude', 'spreadOverwritten' => 'required|integer'];
    }

    public function rules(): array
    {
        return [
            ...self::BEFORE,
            ...$this->dynamicRules(),
            ...self::AFTER,
            'stable' => 'required|integer',
            'parent.name' => 'required|string',
        ];
    }
}

class UnknownAncestorKeyRequest extends FormRequest
{
    private function ancestor(): string
    {
        return 'parent';
    }

    public function rules(): array
    {
        return [
            $this->ancestor() => 'exclude',
            'parent.name' => 'required|string',
            'stable' => 'required|string',
        ];
    }
}

class OptionalAncestorRulesRequest extends FormRequest
{
    /** @return array{parent?: 'exclude', 'parent.name': 'required|string', stable: 'required|string'} */
    private function additionalRules(): array
    {
        return ['parent' => 'exclude', 'parent.name' => 'required|string', 'stable' => 'required|string'];
    }

    public function rules(): array
    {
        return $this->additionalRules();
    }
}

class RootWildcardRulesRequest extends FormRequest
{
    public function rules(): array
    {
        return ['*.name' => 'required|string'];
    }
}

class MultipleReturnsRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->isMethod('POST')) {
            return [
                'shared' => 'required|string',
                'different' => 'required|integer',
                'firstOnly' => 'required|string',
                'payload' => ['required', Rule::array(['name'])],
                'record' => ['required', Rule::array(['name'])],
                'record.name' => 'required|string',
                'pruned' => ['required', 'array', Rule::array(['name', 'other'])],
                'pruned.name' => 'string',
                'parent' => 'exclude',
                'parent.name' => 'required|string',
            ];
        }

        return [
            'shared' => ['required', 'string'],
            'different' => 'required|string',
            'secondOnly' => 'required|string',
            'payload' => ['required', Rule::array(['name'])],
            'record' => ['required', Rule::array(['name'])],
            'record.name' => 'required|string',
            'pruned' => ['required', Rule::array(['name', 'other'])],
            'pruned.name' => 'string',
            'parent.name' => 'required|string',
        ];
    }
}

class DifferentArrayReturnsRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->isMethod('POST')) {
            return ['payload' => ['required', Rule::array(['name'])]];
        }

        return ['payload' => ['required', Rule::array(['email'])]];
    }
}

class DifferentConditionalReturnsRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->isMethod('POST')) {
            return ['payload' => ['required', Rule::when(true, ['string', 'exclude'])]];
        }

        return ['payload' => ['required', Rule::when(true, ['string'])]];
    }
}

class NestedReturnsRequest extends FormRequest
{
    public function rules(): array
    {
        $closure = static function (): array {
            return ['closure' => 'required|string'];
        };

        function nestedFormRequestRules(): array
        {
            return ['function' => 'required|string'];
        }

        $helper = new class {
            public function rules(): array
            {
                return ['nestedClass' => 'required|integer'];
            }
        };

        return ['actual' => 'required|string'];
    }
}

class BroadPhpDocDirectRequest extends FormRequest
{
    /** @return array<mixed> */
    private function broadRules(): array
    {
        return [];
    }

    public function rules(): array
    {
        return $this->broadRules();
    }
}

class LoopBuiltRulesRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [];

        foreach ($this->array('fields') as $field) {
            $rules[$field] = 'required|string';
        }

        return $rules;
    }
}

class IntegerKeyRulesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            0 => 'required|string',
            '1' => 'required|integer',
        ];
    }
}
