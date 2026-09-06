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
        ];
    }
}

function test(AnyOfRequest $request): void
{
    $unpacked = [['string', 'integer']];

    assertType(
        "Illuminate\\Validation\\Rules\\AnyOf<array{'string', array{'integer', 'min:1'}}>",
        Rule::anyOf(['string', ['integer', 'min:1']]),
    );
    assertType('Illuminate\\Validation\\Rules\\AnyOf<array>', Rule::anyOf(...$unpacked));

    assertType('int|string', $request->scalar);
    assertType("'*'|non-empty-array", $request->literalOrArray);
    assertType('(int|numeric-string)', $request->outerInteger);
    assertType("'known'|numeric-string", $request->outerString);
    assertType('mixed', $request->unknownAlternative);
    assertType('int|string|null', $request->nullableScalar);
    assertType('int|string', $request->nullableAlternative);
    assertType('float|int|string', $request->formattedTime);
    assertType("'api'|'import'|int|numeric-string", $request->enumOrInteger);
    assertType('array|int|string', $request->nestedAnyOf);
    assertType('mixed', $request->directAnyOf);
    assertType('mixed', $request->dynamic);
    assertType('array<(int|numeric-string)>|string', $request->collectionOrString);
    assertType("array<'known'|'new'>|string", $request->arrayIn);
    assertType("list<'known'|'new'>|string", $request->listRuleIn);
    assertType('mixed', $request->nestedShape);
    assertType('non-empty-array|non-empty-string', $request->contextualModifiers);
}
