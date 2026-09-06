<?php

declare(strict_types=1);

namespace ValidationRulesLaravel12_8;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\AnyOf;

use function PHPStan\Testing\assertType;

enum Source: string
{
    case Api = 'api';
    case Import = 'import';
}

final class AnyOfRequest extends FormRequest
{
    /** @var list<string> */
    private array $dynamicAlternatives = ['string', 'integer'];

    public function rules(): array
    {
        return [
            'scalar' => ['required', Rule::anyOf(['string|max:255', 'integer'])],
            'requiredScalar' => ['required', Rule::anyOf(['required|string', 'required|integer'])],
            'outerArray' => ['required', 'array', Rule::anyOf(['array|in:a,b', 'string'])],
            'literalOrArray' => ['required', Rule::anyOf([
                ['string', Rule::in(['*'])],
                ['array', 'min:1'],
            ])],
            'outerInteger' => ['required', 'integer', Rule::anyOf([
                [Rule::exists('users', 'id')],
                [Rule::exists('admins', 'id')],
            ])],
            'outerString' => ['required', 'string', Rule::anyOf([
                'integer',
                ['string', Rule::in(['known'])],
            ])],
            'unknownAlternative' => ['required', Rule::anyOf([
                ['string', Rule::in(['new'])],
                [Rule::exists('groups', 'id')],
            ])],
            'nullableScalar' => ['nullable', Rule::anyOf(['string', 'integer'])],
            'nullableAlternative' => ['required', Rule::anyOf(['string', 'nullable|integer'])],
            'formattedTime' => ['required', Rule::anyOf(['date_format:H:i', 'date_format:H:i:s'])],
            'enumOrInteger' => ['required', Rule::anyOf([Rule::enum(Source::class), 'integer'])],
            'nestedAnyOf' => ['required', Rule::anyOf([Rule::anyOf(['string', 'integer']), 'array'])],
            'directAnyOf' => ['required', new AnyOf(['string', 'integer'])],
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
            'contextualModifiers' => ['required', Rule::anyOf([
                ['max:4', 'string', 'min:2'],
                ['array', 'min:1'],
            ])],
            'excludedAlternative' => ['required', Rule::anyOf([['exclude'], ['string']])],
            'excludedStringAlternative' => ['required', Rule::anyOf([['exclude', 'string'], ['integer']])],
            'conditionalExclusion' => ['required', Rule::anyOf([['exclude_if:0,123'], ['string']])],
            'objectExclusion' => ['required', Rule::anyOf([
                [Rule::excludeIf($this->boolean('exclude'))],
                ['string'],
            ])],
            'conditionalRulesExclusion' => ['required', Rule::anyOf([
                [Rule::when($this->boolean('exclude'), 'exclude', 'string')],
                ['string'],
            ])],
            'outerStringWithExclusion' => ['required', 'string', Rule::anyOf([['exclude'], ['integer']])],
            'outerArrayWithExclusion' => ['required', 'array', Rule::anyOf([['exclude'], ['string']])],
            'nestedExclusion' => ['required', Rule::anyOf([['exclude'], ['string']])],
            'nestedExclusion.*' => 'integer',
        ];
    }
}

function test(AnyOfRequest $request): void
{
    assertType('(array|float|int|string|true)', $request->requiredScalar);
    assertType('array', $request->outerArray);

    if (is_array($request->requiredScalar)) {
        assertType('array', $request->requiredScalar);
    }

    $unpacked = [['string', 'integer']];

    assertType(
        "Illuminate\\Validation\\Rules\\AnyOf<array{'string', array{'integer', 'min:1'}}>",
        Rule::anyOf(['string', ['integer', 'min:1']]),
    );
    assertType('Illuminate\\Validation\\Rules\\AnyOf<array>', Rule::anyOf(...$unpacked));

    assertType('(array|float|int|string|true)', $request->scalar);
    assertType("('*'|array)", $request->literalOrArray);
    assertType('(float|int|numeric-string|true)', $request->outerInteger);
    assertType("('known'|numeric-string)", $request->outerString);
    assertType('mixed', $request->unknownAlternative);
    assertType('array|float|int|string|true|null', $request->nullableScalar);
    assertType('(array|float|int|string|true)', $request->nullableAlternative);
    assertType('(array|float|int|string)', $request->formattedTime);
    assertType("('api'|'import'|array|float|int|numeric-string|true)", $request->enumOrInteger);
    assertType('(array|float|int|string|true)', $request->nestedAnyOf);
    assertType('mixed', $request->directAnyOf);
    assertType('mixed', $request->dynamic);
    assertType('array<(float|int|numeric-string|true)>|string', $request->collectionOrString);
    assertType('(array|string)', $request->arrayIn);
    assertType('(array|string)', $request->listRuleIn);
    assertType('mixed', $request->nestedShape);
    assertType('(array|non-empty-string)', $request->contextualModifiers);
    assertType('mixed', $request->excludedAlternative);
    assertType('mixed', $request->excludedStringAlternative);
    assertType('mixed', $request->conditionalExclusion);
    assertType('mixed', $request->objectExclusion);
    assertType('mixed', $request->conditionalRulesExclusion);
    assertType('string', $request->outerStringWithExclusion);
    assertType('array', $request->outerArrayWithExclusion);
    assertType('mixed', $request->nestedExclusion);
    assertType('mixed', $request->validated('nestedExclusion'));
    assertType(
        'array{excludedAlternative: mixed, conditionalRulesExclusion: mixed, outerStringWithExclusion: string, outerArrayWithExclusion: array}',
        $request->safe(['excludedAlternative', 'conditionalRulesExclusion', 'outerStringWithExclusion', 'outerArrayWithExclusion']),
    );
}
