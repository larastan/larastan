<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Validation;

use Illuminate\Support\Stringable as LaravelStringable;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Validator;
use Larastan\Larastan\Support\Validation\ValidationRuleFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ValidationRuleFactoryTest extends TestCase
{
    /** @return iterable<string, array{string|string[], string, bool, bool, bool}> */
    public static function rulesProvider(): iterable
    {
        $looseInteger  = 'int|numeric-string';
        $strictInteger = (new ReflectionMethod(Validator::class, 'validateInteger'))->getNumberOfParameters() >= 3
            ? 'int'
            : $looseInteger;
        $strictBoolean = (new ReflectionMethod(Validator::class, 'validateBoolean'))->getNumberOfParameters() >= 3
            ? 'bool'
            : "true|false|1|0|'1'|'0'";
        $strictNumeric = (new ReflectionMethod(Validator::class, 'validateNumeric'))->getNumberOfParameters() >= 3
            ? 'float|int'
            : 'float|int|numeric-string';

        //             rules, expected type, nullable, possiblyUndefined, required
        yield 'required string' => ['required|string', 'string', false, false, true];
        yield 'sometimes string' => ['sometimes|string', 'string', false, true, false];
        yield 'nullable string' => ['nullable|string', 'string', true, false, false];
        yield 'sometimes nullable string' => ['sometimes|nullable|string', 'string', true, true, false];
        yield 'conditional required is not required' => ['required_if:foo,bar|string', 'string', false, false, false];
        yield 'present guarantees presence' => [['present', 'nullable', 'string'], 'string', true, false, true];
        yield 'conditional present is not presence' => ['present_if:foo,bar|string', 'string', false, false, false];
        yield 'required array' => ['required|array', 'array', false, false, true];
        yield 'required list' => ['required|list', 'list', false, false, true];
        yield 'array rules' => [['required', 'integer'], $looseInteger, false, false, true];
        yield 'in with integer' => ['required|integer|in:0,1', $looseInteger, false, false, true];
        yield 'strict integer' => ['required|integer:strict', $strictInteger, false, false, true];

        yield 'in with strict integer' => [
            'required|integer:strict|in:0,1',
            $strictInteger === 'int' ? '0|1' : $looseInteger,
            false,
            false,
            true,
        ];

        yield 'in without type' => ['required|in:date,rating', "'date'|'rating'", false, false, true];
        yield 'numeric' => ['required|numeric', 'float|int|numeric-string', false, false, true];

        yield 'strict numeric' => ['required|numeric:strict', $strictNumeric, false, false, true];
        yield 'digits' => ['required|digits:2', 'float|int|numeric-string', false, false, true];
        yield 'digits between' => ['required|digits_between:1,2', 'float|int|numeric-string', false, false, true];
        yield 'decimal' => ['required|decimal:2', 'float|int|numeric-string', false, false, true];
        yield 'multiple of' => ['required|multiple_of:0.5', 'float|int|numeric-string', false, false, true];
        yield 'alpha numeric' => ['required|alpha_num', 'float|int|string', false, false, true];
        yield 'starts with' => ['required|starts_with:4', 'float|int|string', false, false, true];
        yield 'regex' => [['required', 'regex:/^[0-9]+$/'], 'float|int|string', false, false, true];
        yield 'email' => ['required|email', 'string|Stringable', false, false, true];
        yield 'IP address' => ['required|ip', 'string|Stringable', false, false, true];
        yield 'MAC address' => ['required|mac_address', 'string|Stringable', false, false, true];
        yield 'JSON' => ['required|json', 'bool|float|int|string|Stringable', false, false, true];
        yield 'strict boolean' => ['required|boolean:strict', $strictBoolean, false, false, true];
        yield 'no type rule' => ['required', 'mixed', false, false, true];
        yield 'same does not establish a type' => ['required|same:other', 'mixed', false, false, true];
        yield 'same preserves a string type' => ['required|string|same:other', 'string', false, false, true];
        yield 'unknown rule preserves a string type' => ['required|string|custom', 'string', false, false, true];
        yield 'between does not establish a type' => ['required|between:1,20', 'mixed', false, false, true];
        yield 'between preserves a string type' => ['required|string|between:1,20', 'string', false, false, true];
        yield 'between preserves a numeric type' => ['required|numeric|between:1,20', 'float|int|numeric-string', false, false, true];
        yield 'integer with min and max' => [['sometimes', 'integer', 'min:1', 'max:20'], $looseInteger, false, true, false];
        yield 'integer with min only' => ['required|integer|min:1', $looseInteger, false, false, true];
        yield 'integer with max only' => ['required|integer|max:20', $looseInteger, false, false, true];
        yield 'integer between' => ['required|integer|between:1,20', $looseInteger, false, false, true];

        yield 'strict integer with min and max' => [
            ['sometimes', 'integer:strict', 'min:1', 'max:20'],
            $strictInteger === 'int' ? 'int<1, 20>' : $looseInteger,
            false,
            true,
            false,
        ];

        yield 'string min max is length not range' => ['required|string|min:1|max:20', 'string', false, false, true];
        yield 'integer with non-numeric min' => ['required|integer|min:', $looseInteger, false, false, true];
        yield 'integer with negative min' => ['required|integer|min:-5|max:5', $looseInteger, false, false, true];
        yield 'integer with min greater than max' => ['required|integer|min:20|max:1', $looseInteger, false, false, true];
        yield 'in wins over min max' => ['required|integer|in:0,1|min:0|max:1', $looseInteger, false, false, true];
    }

    /** @param string|string[] $rules */
    #[DataProvider('rulesProvider')]
    public function testMake(string|array $rules, string $expectedType, bool $nullable, bool $possiblyUndefined, bool $required): void
    {
        $validationRule = ValidationRuleFactory::make($rules);

        $this->assertSame($expectedType, $validationRule->type);
        $this->assertSame($nullable, $validationRule->nullable);
        $this->assertSame($possiblyUndefined, $validationRule->possiblyUndefined);
        $this->assertSame($required, $validationRule->required);
    }

    /** @return iterable<string, array{mixed, bool}> */
    public static function integerValuesProvider(): iterable
    {
        yield 'integer' => [42, true];
        yield 'integer string' => ['42', true];
        yield 'integral float' => [42.0, true];
        yield 'true' => [true, true];

        yield 'stringable object' => [new LaravelStringable('42'), true];

        yield 'decimal string' => ['42.0', false];
        yield 'false' => [false, false];
    }

    #[DataProvider('integerValuesProvider')]
    public function testIntegerRulePreservesAcceptedValues(mixed $value, bool $passes): void
    {
        $validator = new Validator(
            new Translator(new ArrayLoader(), 'en'),
            ['value' => $value],
            ['value' => 'integer'],
        );

        $this->assertSame($passes, $validator->passes());

        if (! $passes) {
            return;
        }

        $this->assertSame($value, $validator->validated()['value']);
    }

    /** @return iterable<string, array{string, mixed, bool}> */
    public static function mappedRuleValuesProvider(): iterable
    {
        yield 'digits accepts a numeric string' => ['digits:2', '42', true];
        yield 'digits accepts an integral float' => ['digits:2', 42.0, true];
        yield 'digits rejects a word' => ['digits:2', 'foo', false];
        yield 'alpha numeric accepts an integer' => ['alpha_num', 42, true];
        yield 'starts with accepts an integer' => ['starts_with:4', 42, true];

        yield 'email accepts a stringable object' => ['email', new LaravelStringable('a@example.com'), true];
        yield 'MAC address accepts a stringable object' => ['mac_address', new LaravelStringable('00:11:22:33:44:55'), true];

        yield 'JSON accepts a float' => ['json', 4.2, true];
        yield 'JSON accepts true' => ['json', true, true];

        yield 'JSON accepts a stringable object' => ['json', new LaravelStringable('{"valid":true}'), true];
    }

    #[DataProvider('mappedRuleValuesProvider')]
    public function testMappedRulePreservesAcceptedValues(string $rule, mixed $value, bool $passes): void
    {
        $validator = new Validator(
            new Translator(new ArrayLoader(), 'en'),
            ['value' => $value],
            ['value' => ['required', $rule]],
        );

        $this->assertSame($passes, $validator->passes());

        if (! $passes) {
            return;
        }

        $this->assertSame($value, $validator->validated()['value']);
    }

    public function testSameRuleAcceptsArrays(): void
    {
        $value = ['one', 'two'];

        $validator = new Validator(
            new Translator(new ArrayLoader(), 'en'),
            ['value' => $value, 'other' => $value],
            ['value' => ['required', 'same:other']],
        );

        $this->assertTrue($validator->passes());
        $this->assertSame($value, $validator->validated()['value']);
    }
}
