<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Validation;

use Larastan\Larastan\Support\Validation\ValidationRuleFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ValidationRuleFactoryTest extends TestCase
{
    /** @return iterable<string, array{string|string[], string, bool, bool, bool}> */
    public static function rulesProvider(): iterable
    {
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
        yield 'array rules' => [['required', 'integer'], 'int', false, false, true];
        yield 'in with integer' => ['required|integer|in:0,1', '0|1', false, false, true];
        yield 'in without type' => ['required|in:date,rating', "'date'|'rating'", false, false, true];
        yield 'numeric' => ['required|numeric', 'float|int|numeric-string', false, false, true];
        yield 'no type rule' => ['required', 'mixed', false, false, true];
        yield 'integer with min and max' => [['sometimes', 'integer', 'min:1', 'max:20'], 'int<1, 20>', false, true, false];
        yield 'integer with min only' => ['required|integer|min:1', 'int<1, max>', false, false, true];
        yield 'integer with max only' => ['required|integer|max:20', 'int<min, 20>', false, false, true];
        yield 'integer between' => ['required|integer|between:1,20', 'int<1, 20>', false, false, true];
        yield 'string min max is length not range' => ['required|string|min:1|max:20', 'string', false, false, true];
        yield 'integer with non-numeric min' => ['required|integer|min:', 'int', false, false, true];
        yield 'integer with negative min' => ['required|integer|min:-5|max:5', 'int<-5, 5>', false, false, true];
        yield 'integer with min greater than max' => ['required|integer|min:20|max:1', 'int', false, false, true];
        yield 'in wins over min max' => ['required|integer|in:0,1|min:0|max:1', '0|1', false, false, true];
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
}
