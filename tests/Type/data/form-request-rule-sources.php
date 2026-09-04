<?php

declare(strict_types=1);

namespace FormRequestRuleSources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

use function PHPStan\Testing\assertType;

class ExactRulesRequest extends FormRequest
{
    public function rules(): array
    {
        return ['exact' => 'required|string'];
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

function testRuleSources(
    ExactRulesRequest $exact,
    InheritedRulesRequest $inherited,
    TraitRulesRequest $trait,
    UnpackedRulesRequest $unpacked,
    MultipleReturnsRequest $multiple,
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
): void {
    assertType('string', $exact->exact);
    assertType('mixed', $exact->unrelated);
    assertType('string', $inherited->exact);
    assertType('(int|numeric-string)', $trait->fromTrait);

    assertType('string', $unpacked->constant);
    assertType('string', $unpacked->overwritten);
    assertType('(int|numeric-string)', $unpacked->stable);
    assertType('mixed', $unpacked->dynamicOnly);

    assertType('string', $multiple->shared);
    assertType('(int|string)', $multiple->different);
    assertType('mixed', $multiple->firstOnly);
    assertType('mixed', $multiple->secondOnly);

    assertType('string', $nested->actual);
    assertType('mixed', $nested->closure);
    assertType('mixed', $nested->function);

    assertType('mixed', $parentComposition->exact);
    assertType('mixed', $parentComposition->composed);
    assertType('(int|numeric-string)', $exactPhpDocDirect->age);
    assertType('string', $exactPhpDocDirect->name);
    assertType('string|Stringable', $exactPhpDocSpread->email);
    assertType('mixed', $broadPhpDocDirect->anything);
    assertType('mixed', $broadPhpDocSpread->broadOnly);
    assertType('string', $broadPhpDocSpread->stable);
    assertType('mixed', $staticRegistry->registry);
    assertType('mixed', $collectionSelection->selected);

    assertType('string', $computed->constantKey);
    assertType('mixed', $computed->dynamicConcatenation);
    assertType('mixed', $computed->ternary);
    assertType('mixed', $computed->coalesce);
    assertType('(int|numeric-string)', $computed->stableComputedSibling);
    assertType('mixed', $loopBuilt->anything);
}
