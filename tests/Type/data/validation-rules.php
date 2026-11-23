<?php

declare(strict_types=1);

namespace ValidationRules;

use App\Casts\UnitEnumeration;
use App\Http\Requests\AdditionalRulesRequest;
use Illuminate\Validation\Rule;

use function PHPStan\Testing\assertType;

function test(AdditionalRulesRequest $request): void
{
    assertType('Illuminate\\Validation\\Rules\\In<array{App\\Casts\\UnitEnumeration::Foo}>', Rule::in(UnitEnumeration::Foo));

    assertType('non-empty-string', $request->emailValue);
    assertType('Illuminate\\Http\\UploadedFile', $request->dimensionsValue);
    assertType('non-empty-string', $request->passwordValue);

    assertType('float|int|numeric-string', $request->digitsValue);
    assertType('float|int|numeric-string', $request->digitsBetweenValue);
    assertType('float|int<min, 2>|numeric-string', $request->decimalMaximumValue);
    assertType('float|int|numeric-string', $request->multipleOfValue);
    assertType('float|int|non-empty-string', $request->alphaNumericValue);
    assertType('float|int|non-empty-string', $request->startsWithValue);
    assertType('float|int|string|null', $request->plainDate);
    assertType('non-empty-string', $request->ipValue);
    assertType('non-empty-string', $request->macAddressValue);
    assertType('bool|float|int|non-empty-string', $request->jsonValue);
    assertType('non-empty-list', $request->listSizeValue);
    assertType('float|int<6, max>|numeric-string', $request->integerGreaterThanValue);
    assertType('float|int<min, 4>|numeric-string', $request->integerLessThanValue);
    assertType('float|int<5, 10>|numeric-string', $request->integerComparisonBoundsValue);
    assertType('float|int|numeric-string', $request->fieldComparisonValue);
    assertType("'baz'|'foo,bar'", $request->quotedInValue);
    assertType('float|int|numeric-string', $request->numericInValue);
    assertType('float|int|numeric-string', $request->numericObjectInValue);
    assertType("0|1|'0'|'1'|bool", $request->booleanInValue);
    assertType('mixed', $request->mixedNumericInValue);
    assertType('mixed', $request->mixedEmptyInValue);
    assertType("'date'|'rating'", $request->textInValue);

    assertType("0|'0'|'false'|'no'|'off'|false", $request->declinedValue);
    assertType("1|'1'|'on'|'true'|'yes'|true", $request->nullableAcceptedValue);
}
