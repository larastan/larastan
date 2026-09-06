<?php

declare(strict_types=1);

namespace ValidationRules;

use App\Casts\BackedEnumeration;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Stringable;

use function PHPStan\Testing\assertType;

enum UnitEnumeration
{
    case Foo;
    case Bar;
}

final class StringableValue implements Stringable
{
    public function __toString(): string
    {
        return 'foo';
    }
}

/** @implements Arrayable<int, int|string> */
final class RuleValues implements Arrayable
{
    /** @return array{1, 'foo'} */
    public function toArray(): array
    {
        return [1, 'foo'];
    }
}

final class AdditionalRulesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'requiredNullable' => ['required', 'nullable', 'string'],
            'presentNullable' => ['present', 'nullable', 'string'],
            'studlyNullable' => ['present', 'Nullable', 'string'],
            'conditionallyRequired' => 'required_if:other,value|string',
            'conditionallyPresent' => 'present_if:other,value|string',
            'digitsValue' => 'required|digits:2',
            'digitsBetweenValue' => 'required|digits_between:1,2',
            'decimalValue' => 'required|decimal:2',
            'multipleOfValue' => 'required|multiple_of:0.5',
            'alphaNumericValue' => 'required|alpha_num',
            'startsWithValue' => 'required|starts_with:4',
            'dateFormatValue' => 'required|date_format:H:i',
            'regexValue' => ['required', 'regex:/^[0-9]+$/'],
            'stringEmailValue' => 'required|email',
            'ipValue' => 'required|ip',
            'macAddressValue' => 'required|mac_address',
            'jsonValue' => 'required|json',
            'sameValue' => 'required|same:other',
            'sameStringValue' => 'required|string|same:other',
            'unknownStringValue' => 'required|string|custom',
            'betweenValue' => 'required|between:1,20',
            'betweenStringValue' => 'required|string|between:1,20',
            'betweenNumericValue' => 'required|numeric|between:1,20',
            'stringMinimumValue' => 'required|min:1|string',
            'arrayMinimumValue' => 'required|array|min:1',
            'listSizeValue' => 'required|size:2|list',
            'sizeValue' => 'required|size:2',
            'comparisonValue' => 'required|gt:other',
            'comparisonArrayValue' => 'required|array|gte:other',
            'numericSizeValue' => 'required|numeric|size:3',
            'jsonIntegerValue' => 'required|integer',
            'numericIntegerValue' => ['required', Rule::numeric()->integer()],
            'numericRawIntegerValue' => 'required|integer|numeric',
            'stringIntegerValue' => 'required|integer|string',
            'numericDigitsValue' => ['required', Rule::numeric()->digits(1)],
            'integerMinimumValue' => 'required|integer|min:1',
            'integerBetweenValue' => 'required|integer|between:1,20',
            'malformedMinimumValue' => 'required|integer|min:',
            'negativeMinimumValue' => 'required|integer|min:-5|max:5',
            'invalidBoundsValue' => 'required|integer|min:20|max:1',
            'boundedInValue' => 'required|integer|in:0,1|min:0|max:1',
            'listBoundsFirst' => 'required|min:1|in:known,new|list',
            'listBoundsLast' => 'required|list|in:known,new|min:1',
            'quotedInValue' => 'required|string|in:"foo,bar",baz',
            'numericInValue' => 'required|numeric|in:1,2',
            'numericObjectInValue' => ['required', 'numeric', Rule::in([1, 2])],
            'booleanInValue' => 'required|boolean|in:0,1',
            'mixedNumericInValue' => 'required|in:1',
            'mixedEmptyInValue' => 'present|in:""',
            'stringNumericInValue' => 'required|string|in:1,2',
            'stringMixedInValue' => 'required|string|in:1,draft',
            'textInValue' => 'required|in:date,rating',
            'acceptedValue' => 'accepted',
            'declinedValue' => 'declined',
            'nullableAcceptedValue' => 'nullable|accepted',
            'nullableDeclinedValue' => 'declined|nullable',
            'sometimesAcceptedValue' => 'sometimes|nullable|accepted',
            'excludedAcceptedValue' => 'exclude_if:other,value|accepted',
            'consents' => 'required|array',
            'consents.terms' => 'nullable|accepted',
            'dateValue' => ['required', Rule::date()],
            'formattedDate' => ['required', Rule::date()->format('Y-m-d')],
            'emailValue' => ['required', Rule::email()],
            'dimensionsValue' => ['required', Rule::dimensions()->maxWidth(1920)],
            'fileValue' => ['required', Rule::file()],
            'imageValue' => ['required', Rule::imageFile()],
            'passwordValue' => ['required', Password::min(8)->letters()->numbers()],
        ];
    }
}

/** @param array{'draft'}|'published' $arrayOrString */
function test(mixed $mixed, array|string $arrayOrString, AdditionalRulesRequest $request): void
{
    assertType("Illuminate\\Validation\\Rules\\In<array{'foo', 'bar'}>", Rule::in(['foo', 'bar']));
    assertType("Illuminate\\Validation\\Rules\\ArrayRule<array{'name', 'email'}>", Rule::array(['name', 'email']));

    assertType("Illuminate\\Validation\\Rules\\In<array{1, 1.5, true, false, null, 'foo'}>", Rule::in([1, 1.5, true, false, null, 'foo']));
    assertType("Illuminate\\Validation\\Rules\\ArrayRule<array{1, 1.5, true, false, null, 'foo'}>", Rule::array([1, 1.5, true, false, null, 'foo']));
    assertType('Illuminate\\Validation\\Rules\\In<array{ValidationRules\\UnitEnumeration::Foo, ValidationRules\\StringableValue}>', Rule::in([UnitEnumeration::Foo, new StringableValue()]));
    assertType('Illuminate\\Validation\\Rules\\ArrayRule<array{ValidationRules\\UnitEnumeration::Foo, ValidationRules\\StringableValue}>', Rule::array([UnitEnumeration::Foo, new StringableValue()]));
    assertType('Illuminate\\Validation\\Rules\\In<array{ValidationRules\\UnitEnumeration::Foo}>', Rule::in(UnitEnumeration::Foo));
    assertType('Illuminate\\Validation\\Rules\\In<array{1, mixed}>', Rule::in([1, $mixed]));
    assertType('Illuminate\\Validation\\Rules\\ArrayRule<array{1, mixed}>', Rule::array([1, $mixed]));
    assertType('Illuminate\\Validation\\Rules\\In<array>', Rule::in($arrayOrString));
    assertType('Illuminate\\Validation\\Rules\\In<array<int, mixed>>', Rule::in(...[['draft']]));
    assertType("Illuminate\\Validation\\Rules\\In<array{'draft'}>", Rule::in(['draft'], 'ignored'));
    assertType("Illuminate\\Validation\\Rules\\In<array{1, 'foo'}>", Rule::in(new RuleValues()));
    assertType("Illuminate\\Validation\\Rules\\ArrayRule<array{1, 'foo'}>", Rule::array(new RuleValues()));
    assertType('Illuminate\\Validation\\Rules\\ArrayRule<array{}>', Rule::array());

    assertType('Illuminate\\Validation\\Rules\\Enum<class-string<App\\Casts\\BackedEnumeration>>', Rule::enum(BackedEnumeration::class));
    assertType('Illuminate\\Validation\\Rules\\Enum<class-string<ValidationRules\\UnitEnumeration>>', Rule::enum(UnitEnumeration::class));

    assertType('Illuminate\\Validation\\Rules\\Numeric<(float|int|numeric-string)>', Rule::numeric());
    assertType('Illuminate\\Validation\\Rules\\Numeric<(float|int|numeric-string)>', Rule::numeric()->digits(3));
    assertType('Illuminate\\Validation\\Rules\\Numeric<(float|int|numeric-string)>', Rule::numeric()->digitsBetween(1, 3));
    assertType('Illuminate\\Validation\\Rules\\Numeric<(float|int|numeric-string)>', Rule::numeric()->exactly(3));
    assertType(
        'Illuminate\\Validation\\Rules\\Numeric<(float|int|numeric-string)>',
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

    assertType('Illuminate\\Validation\\Rules\\Date<DateTimeInterface|float|int|string>', Rule::date());
    assertType(
        'Illuminate\\Validation\\Rules\\Date<float|int|string>',
        Rule::date()->format('Y-m-d')->beforeToday(),
    );

    assertType('DateTimeInterface|float|int|string', $request->dateValue);
    assertType('float|int|string', $request->formattedDate);
    assertType('string', $request->emailValue);
    assertType('Illuminate\\Http\\UploadedFile', $request->dimensionsValue);
    assertType('Illuminate\\Http\\UploadedFile', $request->fileValue);
    assertType('Illuminate\\Http\\UploadedFile', $request->imageValue);
    assertType('string', $request->passwordValue);

    assertType('string', $request->requiredNullable);
    assertType('string|null', $request->presentNullable);
    assertType('string|null', $request->studlyNullable);
    assertType('string|null', $request->conditionallyRequired);
    assertType('string|null', $request->conditionallyPresent);
    assertType('(float|int|numeric-string)', $request->digitsValue);
    assertType('(float|int|numeric-string)', $request->digitsBetweenValue);
    assertType('(float|int|numeric-string)', $request->decimalValue);
    assertType('(float|int|numeric-string)', $request->multipleOfValue);
    assertType('float|int|string', $request->alphaNumericValue);
    assertType('float|int|string', $request->startsWithValue);
    assertType('float|int|string', $request->dateFormatValue);
    assertType('float|int|string', $request->regexValue);
    assertType('string', $request->stringEmailValue);
    assertType('string', $request->ipValue);
    assertType('string', $request->macAddressValue);
    assertType('bool|float|int|string', $request->jsonValue);
    assertType('mixed', $request->sameValue);
    assertType('string', $request->sameStringValue);
    assertType('string', $request->unknownStringValue);
    assertType('mixed', $request->betweenValue);
    assertType('non-empty-string', $request->betweenStringValue);
    assertType('(float|int|numeric-string)', $request->betweenNumericValue);
    assertType('non-empty-string', $request->stringMinimumValue);
    assertType('non-empty-array', $request->arrayMinimumValue);
    assertType('non-empty-list', $request->listSizeValue);
    assertType('mixed', $request->sizeValue);
    assertType('mixed', $request->comparisonValue);
    assertType('array', $request->comparisonArrayValue);
    assertType('(float|int|numeric-string)', $request->numericSizeValue);
    assertType('(float|int|numeric-string|true)', $request->jsonIntegerValue);
    assertType('(float|int|numeric-string)', $request->numericIntegerValue);
    assertType('(float|int|numeric-string)', $request->numericRawIntegerValue);
    assertType('numeric-string', $request->stringIntegerValue);
    assertType('(float|int|numeric-string)', $request->numericDigitsValue);
    assertType('(float|int|numeric-string|true)', $request->integerMinimumValue);
    assertType('(float|int|numeric-string|true)', $request->integerBetweenValue);
    assertType('(float|int|numeric-string|true)', $request->malformedMinimumValue);
    assertType('(float|int|numeric-string|true)', $request->negativeMinimumValue);
    assertType('(float|int|numeric-string|true)', $request->invalidBoundsValue);
    assertType('(float|int|numeric-string|true)', $request->boundedInValue);
    assertType("non-empty-list<'known'|'new'>", $request->listBoundsFirst);
    assertType("non-empty-list<'known'|'new'>", $request->listBoundsLast);
    assertType("'baz'|'foo,bar'", $request->quotedInValue);
    assertType('(float|int|numeric-string)', $request->numericInValue);
    assertType('(float|int|numeric-string)', $request->numericObjectInValue);
    assertType("0|1|'0'|'1'|bool", $request->booleanInValue);
    assertType('mixed', $request->mixedNumericInValue);
    assertType('mixed', $request->mixedEmptyInValue);
    assertType("'date'|'rating'", $request->textInValue);
    assertType(
        "array{numericInValue: (float|int|numeric-string), booleanInValue: 0|1|'0'|'1'|bool, mixedNumericInValue: mixed}",
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
