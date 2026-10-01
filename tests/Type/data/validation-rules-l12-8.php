<?php

declare(strict_types=1);

namespace ValidationRulesLaravel12_8;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\AnyOf;

use function PHPStan\Testing\assertType;

function test(AnyOfRequest $request): void
{
    $unpacked = [['string', 'integer']];

    assertType('Illuminate\\Validation\\Rules\\AnyOf<array>', Rule::anyOf(...$unpacked));

    assertType('mixed', $request->dynamic);
    assertType('array<float|int|numeric-string>|non-empty-string', $request->collectionOrString);
    assertType('(array|non-empty-string)', $request->arrayIn);
    assertType('(array|non-empty-string)', $request->listRuleIn);
    assertType('mixed', $request->nestedShape);
    assertType('mixed', $request->excludedStringAlternative);
}

class AnyOfRequest extends FormRequest
{
    /** @var list<string> */
    private array $dynamicAlternatives = ['string', 'integer'];

    public function rules(): array
    {
        return [
            'dynamic' => ['required', Rule::anyOf($this->dynamicAlternatives)],
            'collectionOrString' => ['required', Rule::anyOf(['string', 'array'])],
            'collectionOrString.*' => ['integer'],
            'arrayIn' => ['required', Rule::anyOf([
                ['array', 'in:known,new'],
                'string',
            ])],
            'listRuleIn' => ['required', Rule::anyOf([
                ['list', Rule::in(['known', 'new'])],
                'string',
            ])],
            'nestedShape' => ['required', Rule::anyOf([
                ['type' => ['required', 'string']],
            ])],
            'excludedStringAlternative' => ['required', Rule::anyOf([['exclude', 'string'], ['integer']])],
        ];
    }
}
