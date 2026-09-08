<?php

declare(strict_types=1);

namespace ValidationRulesLaravel12_8;

use App\Http\Requests\AnyOfRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\AnyOf;

use function PHPStan\Testing\assertType;

function test(AnyOfRequest $request): void
{
    assertType('(array|float|int|non-empty-string|numeric-string)', $request->requiredScalar);
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

    assertType('(array|float|int|non-empty-string)', $request->scalar);
    assertType("('*'|array)", $request->literalOrArray);
    assertType('float|int|numeric-string', $request->outerInteger);
    assertType("('known'|numeric-string)", $request->outerString);
    assertType('mixed', $request->unknownAlternative);
    assertType('array|float|int|string|null', $request->nullableScalar);
    assertType('(array|float|int|non-empty-string)', $request->nullableAlternative);
    assertType('(array|non-empty-string)', $request->formattedTime);
    assertType("('api'|'import'|array|float|int|numeric-string)", $request->enumOrInteger);
    assertType('(array|float|int|non-empty-string)', $request->nestedAnyOf);
    assertType('mixed', $request->directAnyOf);
    assertType('mixed', $request->dynamic);
    assertType('array<float|int|numeric-string>|non-empty-string', $request->collectionOrString);
    assertType('(array|non-empty-string)', $request->arrayIn);
    assertType('(array|non-empty-string)', $request->listRuleIn);
    assertType('mixed', $request->nestedShape);
    assertType('(array|non-empty-string)', $request->contextualModifiers);
    assertType('mixed', $request->excludedAlternative);
    assertType('mixed', $request->excludedStringAlternative);
    assertType('mixed', $request->conditionalExclusion);
    assertType('mixed', $request->objectExclusion);
    assertType('mixed', $request->conditionalRulesExclusion);
    assertType('non-empty-string', $request->outerStringWithExclusion);
    assertType('array', $request->outerArrayWithExclusion);
    assertType('mixed', $request->nestedExclusion);
    assertType('mixed', $request->validated('nestedExclusion'));
    assertType(
        'array{excludedAlternative: mixed, conditionalRulesExclusion: mixed, outerStringWithExclusion: non-empty-string, outerArrayWithExclusion: array}',
        $request->safe(['excludedAlternative', 'conditionalRulesExclusion', 'outerStringWithExclusion', 'outerArrayWithExclusion']),
    );
}
