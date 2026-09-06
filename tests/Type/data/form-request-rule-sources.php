<?php

declare(strict_types=1);

namespace FormRequestRuleSources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use function PHPStan\Testing\assertType;

interface RequestMarker
{
}

class ExactRulesRequest extends FormRequest
{
    public function rules(): array
    {
        assertType('mixed', $this->exact);

        return ['exact' => 'required|string'];
    }

    public function authorize(): bool
    {
        assertType('mixed', $this->exact);

        return true;
    }

    protected function prepareForValidation(): void
    {
        assertType('mixed', $this->exact);

        $alias = $this;
        assertType('mixed', $alias->exact);

        if (is_string($alias->exact)) {
            assertType('string', $alias->exact);
        }

        $other = new ExactRulesRequest();
        assertType('string', $other->exact);

        if ($this instanceof RequestMarker) {
            assertType('mixed', $this->exact);

            $intersectionAlias = $this;
            assertType('mixed', $intersectionAlias->exact);
        }

        if (is_string($this->exact)) {
            assertType('string', $this->exact);
        }

        (function (): void {
            assertType('mixed', $this->exact);
        })();
    }

    public function isPrecognitive(): bool
    {
        assertType('mixed', $this->exact);

        return parent::isPrecognitive();
    }

    public function filterPrecognitiveRules($rules)
    {
        assertType('mixed', $this->exact);

        return parent::filterPrecognitiveRules($rules);
    }

    protected function passedValidation(): void
    {
        assertType('string', $this->exact);

        (function (): void {
            assertType('string', $this->exact);
        })();
    }

    public function unrelated(): array
    {
        return ['unrelated' => 'required|integer'];
    }

    public function toDto(): string
    {
        assertType('string', $this->exact);

        return $this->exact;
    }
}

/** @property int $exact */
class AnnotatedRulesRequest extends ExactRulesRequest
{
    public function authorize(): bool
    {
        assertType('int', $this->exact);

        $alias = $this;
        assertType('int', $alias->exact);

        return true;
    }
}

class InheritedRulesRequest extends ExactRulesRequest
{
}

trait ProvidesRules
{
    public function rules(): array
    {
        return ['fromTrait' => 'required|integer'];
    }
}

class TraitRulesRequest extends FormRequest
{
    use ProvidesRules;
}

class UnpackedRulesRequest extends FormRequest
{
    private const RULES = ['constant' => 'required|string'];

    /** @return array<string, string> */
    private function dynamicRules(): array
    {
        return ['dynamicOnly' => 'required|string'];
    }

    public function rules(): array
    {
        return [
            'overwritten' => 'required|string',
            ...$this->dynamicRules(),
            ...self::RULES,
            'stable' => 'required|integer',
        ];
    }
}

class OverwrittenSpreadRulesRequest extends FormRequest
{
    private const RULES = ['overwritten' => 'required|string'];

    /** @return array<string, string> */
    private function dynamicRules(): array
    {
        return ['overwritten' => 'required|integer'];
    }

    public function rules(): array
    {
        return [...self::RULES, ...$this->dynamicRules(), 'stable' => 'required|string'];
    }
}

class UnknownAncestorRulesRequest extends FormRequest
{
    /** @return array<string, string> */
    private function additionalRules(): array
    {
        return ['parent' => 'exclude'];
    }

    public function rules(): array
    {
        return [
            ...$this->additionalRules(),
            'parent.name' => 'required|string',
            'stable' => 'required|string',
            'v1\.0' => 'required|string',
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

class NumericSpreadRulesRequest extends FormRequest
{
    /** @return array<int, string> */
    private function additionalRules(): array
    {
        return [0 => 'exclude'];
    }

    public function rules(): array
    {
        return [
            'before' => 'required|string',
            ...$this->additionalRules(),
            'parent.name' => 'required|string',
        ];
    }
}

class ExplicitAncestorRulesRequest extends FormRequest
{
    /** @return array<string, string> */
    private function additionalRules(): array
    {
        return ['parent' => 'exclude'];
    }

    public function rules(): array
    {
        return [
            ...$this->additionalRules(),
            'parent' => 'required|array',
            'parent.name' => 'required|string',
        ];
    }
}

class UnrelatedSpreadRulesRequest extends FormRequest
{
    /** @return array<'other', string> */
    private function additionalRules(): array
    {
        return ['other' => 'exclude'];
    }

    public function rules(): array
    {
        return [...$this->additionalRules(), 'parent.name' => 'required|string'];
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

class BranchAncestorRulesRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->boolean('exclude')) {
            return ['parent' => 'exclude', 'parent.name' => 'required|string', 'stable' => 'required|string'];
        }

        return ['parent.name' => 'required|string', 'stable' => 'required|string'];
    }
}

class WildcardAncestorRulesRequest extends FormRequest
{
    /** @return array<'parent.*'|'other', string> */
    private function additionalRules(): array
    {
        return ['parent.*' => 'exclude'];
    }

    public function rules(): array
    {
        return [
            ...$this->additionalRules(),
            'parent.item.name' => 'required|string',
            'unrelated.name' => 'required|string',
        ];
    }
}

class RootWildcardRulesRequest extends FormRequest
{
    public function rules(): array
    {
        return ['*.name' => 'required|string'];
    }
}

class RootWildcardWithSiblingRulesRequest extends FormRequest
{
    public function rules(): array
    {
        return ['payload' => 'required|array', '*' => 'exclude'];
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
            ];
        }

        return [
            'shared' => ['required', 'string'],
            'different' => 'required|string',
            'secondOnly' => 'required|string',
        ];
    }
}

class EquivalentArrayReturnsRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->isMethod('POST')) {
            return [
                'payload' => ['required', Rule::array(['name'])],
                'record' => ['required', Rule::array(['name'])],
                'record.name' => 'required|string',
            ];
        }

        return [
            'payload' => ['required', Rule::array(['name'])],
            'record' => ['required', Rule::array(['name'])],
            'record.name' => 'required|string',
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

class MixedArrayPruningReturnsRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->isMethod('POST')) {
            return [
                'payload' => ['required', 'array', Rule::array(['name', 'other'])],
                'payload.name' => 'string',
            ];
        }

        return [
            'payload' => ['required', Rule::array(['name', 'other'])],
            'payload.name' => 'string',
        ];
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

class ParentCompositionRequest extends ExactRulesRequest
{
    public function rules(): array
    {
        return parent::rules() + ['composed' => 'required|string'];
    }
}

class RuleRegistryModel extends Model
{
    /** @return array{age: array{'integer', 'required'}, name: 'required|string'} */
    public static function exactValidationRules(): array
    {
        return [
            'age' => ['integer', 'required'],
            'name' => 'required|string',
        ];
    }

    /** @return array<string, string> */
    public static function validationRules(): array
    {
        return ['registry' => 'required|string'];
    }
}

class ExactPhpDocDirectRequest extends FormRequest
{
    public function rules(): array
    {
        return RuleRegistryModel::exactValidationRules();
    }
}

class ExactPhpDocSpreadRequest extends FormRequest
{
    /** @return array{email: array{'required', 'email'}} */
    private function commonRules(): array
    {
        return ['email' => ['required', 'email']];
    }

    public function rules(): array
    {
        return [...$this->commonRules()];
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

class BroadPhpDocSpreadRequest extends FormRequest
{
    /** @return array<string, mixed> */
    private function commonRules(): array
    {
        return ['broadOnly' => 'required|string'];
    }

    public function rules(): array
    {
        return [
            ...$this->commonRules(),
            'stable' => 'required|string',
        ];
    }
}

class StaticRegistryRequest extends FormRequest
{
    public function rules(): array
    {
        return RuleRegistryModel::validationRules();
    }
}

class CollectionSelectionRequest extends FormRequest
{
    public function rules(): array
    {
        return collect([
            'selected' => 'required|string',
            'discarded' => 'required|integer',
        ])->only(['selected'])->all();
    }
}

class ComputedRulesRequest extends FormRequest
{
    public function rules(): array
    {
        $constantKey = 'constantKey';
        $dynamicKey  = $this->method();
        $dynamicRule = 'required|' . $this->string('rule');
        $ternaryRule = $this->isMethod('POST') ? 'required|string' : 'required|integer';
        $coalesceRule = $this->input('rule') ?? 'required|string';

        return [
            $dynamicKey => 'required|string',
            $constantKey => 'required|string',
            'dynamicConcatenation' => $dynamicRule,
            'ternary' => $ternaryRule,
            'coalesce' => $coalesceRule,
            'stableComputedSibling' => 'required|integer',
        ];
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

function testRuleSources(
    ExactRulesRequest $exact,
    InheritedRulesRequest $inherited,
    TraitRulesRequest $trait,
    UnpackedRulesRequest $unpacked,
    OverwrittenSpreadRulesRequest $overwrittenSpread,
    UnknownAncestorRulesRequest $unknownAncestor,
    UnknownAncestorKeyRequest $unknownAncestorKey,
    NumericSpreadRulesRequest $numericSpread,
    ExplicitAncestorRulesRequest $explicitAncestor,
    UnrelatedSpreadRulesRequest $unrelatedSpread,
    OptionalAncestorRulesRequest $optionalAncestor,
    BranchAncestorRulesRequest $branchAncestor,
    WildcardAncestorRulesRequest $wildcardAncestor,
    RootWildcardRulesRequest $rootWildcard,
    RootWildcardWithSiblingRulesRequest $rootWildcardWithSibling,
    MultipleReturnsRequest $multiple,
    EquivalentArrayReturnsRequest $equivalentArrays,
    DifferentArrayReturnsRequest $differentArrays,
    MixedArrayPruningReturnsRequest $mixedPruning,
    DifferentConditionalReturnsRequest $differentConditions,
    NestedReturnsRequest $nested,
    ParentCompositionRequest $parentComposition,
    ExactPhpDocDirectRequest $exactPhpDocDirect,
    ExactPhpDocSpreadRequest $exactPhpDocSpread,
    BroadPhpDocDirectRequest $broadPhpDocDirect,
    BroadPhpDocSpreadRequest $broadPhpDocSpread,
    StaticRegistryRequest $staticRegistry,
    CollectionSelectionRequest $collectionSelection,
    ComputedRulesRequest $computed,
    LoopBuiltRulesRequest $loopBuilt,
    IntegerKeyRulesRequest $integerKeys,
): void {
    assertType('string', $exact->exact);
    assertType('mixed', $exact->unrelated);
    assertType('string', $inherited->exact);
    assertType('(float|int|numeric-string|true)', $trait->fromTrait);

    assertType('string', $unpacked->constant);
    assertType('mixed', $unpacked->overwritten);
    assertType('(float|int|numeric-string|true)', $unpacked->stable);
    assertType('mixed', $unpacked->dynamicOnly);
    assertType(
        'array{constant: string, stable: (float|int|numeric-string|true), ...}',
        $unpacked->validated(),
    );

    assertType('mixed', $overwrittenSpread->overwritten);
    assertType('string', $overwrittenSpread->stable);
    assertType('array{stable: string, ...}', $overwrittenSpread->validated());

    assertType('mixed', $unknownAncestor->parent);
    assertType('string', $unknownAncestor->stable);
    assertType('string', $unknownAncestor->{'v1.0'});
    assertType("array{stable: string, 'v1.0': string, ...}", $unknownAncestor->validated());
    assertType('mixed', $unknownAncestorKey->parent);
    assertType('string', $unknownAncestorKey->stable);
    assertType('array{stable: string, ...}', $unknownAncestorKey->validated());
    assertType('string', $numericSpread->before);
    assertType('array{name: string, ...}', $numericSpread->parent);
    assertType('array{before: string, parent: array{name: string}, ...}', $numericSpread->validated());
    assertType('array{name: string, ...}', $explicitAncestor->parent);
    assertType('array{parent: array{name: string}, ...}', $explicitAncestor->validated());
    assertType('array{name: string, ...}', $unrelatedSpread->parent);
    assertType('array{parent: array{name: string}, ...}', $unrelatedSpread->validated());
    assertType('mixed', $optionalAncestor->parent);
    assertType('array{stable: string, ...}', $optionalAncestor->validated());
    assertType('mixed', $branchAncestor->parent);
    assertType('array{stable: string, ...}', $branchAncestor->validated());
    assertType('mixed', $wildcardAncestor->parent);
    assertType('array{name: string, ...}', $wildcardAncestor->unrelated);
    assertType('array{unrelated: array{name: string}, ...}', $wildcardAncestor->validated());

    assertType('mixed', $rootWildcard->{'*'});
    assertType('array', $rootWildcard->validated());
    assertType('mixed', $rootWildcard->validated('0.name'));
    assertType('array<string, mixed>', $rootWildcard->safe(['0.name']));
    assertType('mixed', $rootWildcardWithSibling->payload);
    assertType('array', $rootWildcardWithSibling->validated());

    assertType('string', $multiple->shared);
    assertType('(float|int|string|true)', $multiple->different);
    assertType('mixed', $multiple->firstOnly);
    assertType('mixed', $multiple->secondOnly);

    assertType('array{payload: array{name?: mixed}, record: array{name: string}}', $equivalentArrays->validated());
    assertType('string', $equivalentArrays->validated('record.name'));
    assertType('array', $differentArrays->validated());
    assertType('array{payload?: array{name?: string, other?: mixed}}', $mixedPruning->validated());
    assertType('array{name?: string, other?: mixed}|null', $mixedPruning->validated('payload'));
    assertType('array', $differentConditions->validated());

    assertType('string', $nested->actual);
    assertType('mixed', $nested->closure);
    assertType('mixed', $nested->function);
    assertType('mixed', $nested->nestedClass);
    assertType('array{actual: string}', $nested->validated());

    assertType('mixed', $parentComposition->exact);
    assertType('mixed', $parentComposition->composed);
    assertType('(float|int|numeric-string|true)', $exactPhpDocDirect->age);
    assertType('string', $exactPhpDocDirect->name);
    assertType('string', $exactPhpDocSpread->email);
    assertType('mixed', $broadPhpDocDirect->anything);
    assertType('mixed', $broadPhpDocSpread->broadOnly);
    assertType('string', $broadPhpDocSpread->stable);
    assertType('mixed', $staticRegistry->registry);
    assertType('mixed', $collectionSelection->selected);

    assertType('string', $computed->constantKey);
    assertType('mixed', $computed->dynamicConcatenation);
    assertType('mixed', $computed->ternary);
    assertType('mixed', $computed->coalesce);
    assertType('(float|int|numeric-string|true)', $computed->stableComputedSibling);
    assertType(
        'array{constantKey: string, dynamicConcatenation?: mixed, ternary?: mixed, coalesce?: mixed, stableComputedSibling: (float|int|numeric-string|true), ...}',
        $computed->validated(),
    );
    assertType('mixed', $loopBuilt->anything);

    assertType('array{shared: string, different: (float|int|string|true), ...}', $multiple->validated());

    assertType('mixed', $integerKeys->{'0'});
    assertType('mixed', $integerKeys->{'1'});
    assertType('array', $integerKeys->validated());
    assertType('mixed', $integerKeys->validated(0));
}
