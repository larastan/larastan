<?php

declare(strict_types=1);

namespace ValidationRules;

use App\Casts\BackedEnumeration;
use App\Casts\UnitEnumeration;
use App\Http\Requests\AdditionalRulesRequest;
use App\ValueObjects\RuleValues;
use App\ValueObjects\StringableValue;
use Illuminate\Validation\Rule;

use function PHPStan\Testing\assertType;

/** @param array{'draft'}|'published' $arrayOrString */
function test(mixed $mixed, array|string $arrayOrString, AdditionalRulesRequest $request): void
{
    assertType("Illuminate\\Validation\\Rules\\In<array{'foo', 'bar'}>", Rule::in(['foo', 'bar']));
    assertType("Illuminate\\Validation\\Rules\\ArrayRule<array{'name', 'email'}>", Rule::array(['name', 'email']));

    assertType("Illuminate\\Validation\\Rules\\In<array{1, 1.5, true, false, null, 'foo'}>", Rule::in([1, 1.5, true, false, null, 'foo']));
    assertType("Illuminate\\Validation\\Rules\\ArrayRule<array{1, 1.5, true, false, null, 'foo'}>", Rule::array([1, 1.5, true, false, null, 'foo']));
    assertType('Illuminate\\Validation\\Rules\\In<array{App\\Casts\\UnitEnumeration::Foo, App\\ValueObjects\\StringableValue}>', Rule::in([UnitEnumeration::Foo, new StringableValue()]));
    assertType('Illuminate\\Validation\\Rules\\ArrayRule<array{App\\Casts\\UnitEnumeration::Foo, App\\ValueObjects\\StringableValue}>', Rule::array([UnitEnumeration::Foo, new StringableValue()]));
    assertType('Illuminate\\Validation\\Rules\\In<array{App\\Casts\\UnitEnumeration::Foo}>', Rule::in(UnitEnumeration::Foo));
    assertType('Illuminate\\Validation\\Rules\\In<array{1, mixed}>', Rule::in([1, $mixed]));
    assertType('Illuminate\\Validation\\Rules\\ArrayRule<array{1, mixed}>', Rule::array([1, $mixed]));
    assertType('Illuminate\\Validation\\Rules\\In<array>', Rule::in($arrayOrString));
    assertType('Illuminate\\Validation\\Rules\\In<array<int, mixed>>', Rule::in(...[['draft']]));
    assertType("Illuminate\\Validation\\Rules\\In<array{'draft'}>", Rule::in(['draft'], 'ignored'));
    assertType("Illuminate\\Validation\\Rules\\In<array{1, 'foo'}>", Rule::in(new RuleValues()));
    assertType("Illuminate\\Validation\\Rules\\ArrayRule<array{1, 'foo'}>", Rule::array(new RuleValues()));
    assertType('Illuminate\\Validation\\Rules\\ArrayRule<array{}>', Rule::array());

    assertType('Illuminate\\Validation\\Rules\\Enum<class-string<App\\Casts\\BackedEnumeration>>', Rule::enum(BackedEnumeration::class));
    assertType('Illuminate\\Validation\\Rules\\Enum<class-string<App\\Casts\\UnitEnumeration>>', Rule::enum(UnitEnumeration::class));

    assertType('Illuminate\\Validation\\Rules\\Numeric<float|int|numeric-string>', Rule::numeric());
    assertType('Illuminate\\Validation\\Rules\\Numeric<float|int|numeric-string>', Rule::numeric()->digits(3));
    assertType('Illuminate\\Validation\\Rules\\Numeric<float|int|numeric-string>', Rule::numeric()->digitsBetween(1, 3));
    assertType('Illuminate\\Validation\\Rules\\Numeric<float|int|numeric-string>', Rule::numeric()->exactly(3));
    assertType(
        'Illuminate\\Validation\\Rules\\Numeric<float|int|numeric-string>',
        Rule::numeric()
            ->integer()
            ->between(1, 10)
            ->decimal(2, 4)
            ->different('other')
            ->greaterThan('minimum')
            ->greaterThanOrEqualTo('minimum')
            ->lessThan('maximum')
            ->lessThanOrEqualTo('maximum')
            ->max(10)
            ->maxDigits(3)
            ->min(1)
            ->minDigits(1)
            ->multipleOf(0.5)
            ->same('confirmation'),
    );

    assertType('Illuminate\\Validation\\Rules\\Date<string>', Rule::date());
    assertType(
        'Illuminate\\Validation\\Rules\\Date<string>',
        Rule::date()->format('Y-m-d')->beforeToday(),
    );

    assertType('non-empty-string', $request->dateValue);
    assertType('non-empty-string', $request->formattedDate);
    assertType('non-empty-string', $request->emailValue);
    assertType('Illuminate\\Http\\UploadedFile', $request->dimensionsValue);
    assertType('Illuminate\\Http\\UploadedFile', $request->fileValue);
    assertType('Illuminate\\Http\\UploadedFile', $request->imageValue);
    assertType('non-empty-string', $request->passwordValue);

    assertType('non-empty-string', $request->requiredNullable);
    assertType("'0'", $request->requiredZero);
    assertType('string|null', $request->presentNullable);
    assertType('string|null', $request->studlyNullable);
    assertType('string|null', $request->conditionallyRequired);
    assertType('string|null', $request->conditionallyPresent);
    assertType('float|int|numeric-string', $request->digitsValue);
    assertType('float|int|numeric-string', $request->digitsBetweenValue);
    assertType('float|int|numeric-string', $request->decimalValue);
    assertType('float|int<min, 2>|numeric-string', $request->decimalMaximumValue);
    assertType('float|int|numeric-string', $request->multipleOfValue);
    assertType('float|int|non-empty-string', $request->alphaNumericValue);
    assertType('float|int|non-empty-string', $request->startsWithValue);
    assertType('non-empty-string', $request->dateFormatValue);
    assertType('string|null', $request->targetDate);
    assertType('string|null', $request->validated('targetDate'));
    assertType('string|null', $request->plainDate);
    assertType('non-empty-string', $request->numericDate);
    assertType('float|int|non-empty-string', $request->regexValue);
    assertType('non-empty-string', $request->stringEmailValue);
    assertType('non-empty-string', $request->ipValue);
    assertType('non-empty-string', $request->macAddressValue);
    assertType('bool|float|int|non-empty-string', $request->jsonValue);
    assertType('mixed', $request->sameValue);
    assertType('non-empty-string', $request->sameStringValue);
    assertType('non-empty-string', $request->unknownStringValue);
    assertType('mixed', $request->betweenValue);
    assertType('non-empty-string', $request->betweenStringValue);
    assertType('float|int<1, 20>|numeric-string', $request->betweenNumericValue);
    assertType('non-empty-string', $request->stringMinimumValue);
    assertType('non-empty-array', $request->arrayMinimumValue);
    assertType('non-empty-list', $request->listSizeValue);
    assertType('mixed', $request->sizeValue);
    assertType('mixed', $request->comparisonValue);
    assertType('array', $request->comparisonArrayValue);
    assertType('3|float|numeric-string', $request->numericSizeValue);
    assertType('float|int|numeric-string', $request->jsonIntegerValue);
    assertType('float|int|numeric-string', $request->numericIntegerValue);
    assertType('float|int|numeric-string', $request->numericRawIntegerValue);
    assertType('numeric-string', $request->stringIntegerValue);
    assertType('float|int|numeric-string', $request->numericDigitsValue);
    assertType('float|int<1, max>|numeric-string', $request->integerMinimumValue);
    assertType('float|int<min, 50>|numeric-string', $request->integerMaximumValue);
    assertType('float|int<1, 50>|numeric-string', $request->integerLimitValue);
    assertType('float|int<1, 20>|numeric-string', $request->integerBetweenValue);
    assertType('float|int<10, 15>|numeric-string', $request->integerRepeatedBoundsValue);
    assertType('3|float|numeric-string', $request->integerSizeValue);
    assertType('float|int|numeric-string', $request->malformedMinimumValue);
    assertType('float|int<-5, 5>|numeric-string', $request->negativeMinimumValue);
    assertType('float|int|numeric-string', $request->invalidBoundsValue);
    assertType('float|int<0, 1>|numeric-string', $request->boundedInValue);
    assertType('float|int|non-empty-string', $request->regexLengthValue);
    assertType('float|int|numeric-string', $request->digitsLengthValue);
    assertType("non-empty-list<'known'|'new'>", $request->listBoundsFirst);
    assertType("non-empty-list<'known'|'new'>", $request->listBoundsLast);
    assertType("'baz'|'foo,bar'", $request->quotedInValue);
    assertType('float|int|numeric-string', $request->numericInValue);
    assertType('float|int|numeric-string', $request->numericObjectInValue);
    assertType("0|1|'0'|'1'|bool", $request->booleanInValue);
    assertType('mixed', $request->mixedNumericInValue);
    assertType('mixed', $request->mixedEmptyInValue);
    assertType("'date'|'rating'", $request->textInValue);
    assertType(
        "array{numericInValue: float|int|numeric-string, booleanInValue: 0|1|'0'|'1'|bool, mixedNumericInValue: mixed}",
        $request->safe(['numericInValue', 'booleanInValue', 'mixedNumericInValue']),
    );

    assertType("1|'1'|'on'|'true'|'yes'|true", $request->acceptedValue);
    assertType("0|'0'|'false'|'no'|'off'|false", $request->declinedValue);
    assertType("1|'1'|'on'|'true'|'yes'|true", $request->nullableAcceptedValue);
    assertType("0|'0'|'false'|'no'|'off'|false", $request->nullableDeclinedValue);
    assertType("1|'1'|'on'|'true'|'yes'|true|null", $request->sometimesAcceptedValue);
    assertType('mixed', $request->excludedAcceptedValue);
    assertType(
        "array{acceptedValue: 1|'1'|'on'|'true'|'yes'|true, declinedValue: 0|'0'|'false'|'no'|'off'|false}",
        $request->safe(['acceptedValue', 'declinedValue']),
    );
    assertType(
        "array{nullableAcceptedValue: 1|'1'|'on'|'true'|'yes'|true, nullableDeclinedValue: 0|'0'|'false'|'no'|'off'|false}",
        $request->safe(['nullableAcceptedValue', 'nullableDeclinedValue']),
    );
    assertType(
        "array{sometimesAcceptedValue?: 1|'1'|'on'|'true'|'yes'|true, excludedAcceptedValue?: 1|'1'|'on'|'true'|'yes'|true}",
        $request->safe(['sometimesAcceptedValue', 'excludedAcceptedValue']),
    );
    assertType("array{terms: 1|'1'|'on'|'true'|'yes'|true}", $request->validated('consents'));
}
