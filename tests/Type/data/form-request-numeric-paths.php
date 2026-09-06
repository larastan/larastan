<?php

declare(strict_types=1);

namespace FormRequestNumericPaths;

use Illuminate\Foundation\Http\FormRequest;

use function PHPStan\Testing\assertType;

class NamedFieldRequest extends FormRequest
{
    public function rules(): array
    {
        return ['name' => 'required|string'];
    }
}

class NumericArrayKeysRequest extends FormRequest
{
    public function rules(): array
    {
        return ['numeric' => 'required|array:0,1'];
    }
}

function testNumericSelectors(NamedFieldRequest $request): void
{
    assertType('array{}', $request->safe(['0']));
    assertType('array{}', $request->safe(['-1']));
    assertType('array{}', $request->safe(['01', '+1', '-01']));
    assertType('array{name: string}', $request->safe(['-1', 'name', '0']));
    assertType('null', $request->validated('0'));
    assertType('42', $request->validated('-1', 42));
}

function testNumericArrayKeys(NumericArrayKeysRequest $request): void
{
    assertType('array{numeric?: array{0?: mixed}}', $request->safe(['numeric.0']));
    assertType('array{}', $request->safe(['numeric.-1']));
}

class NumericSegmentsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'negative.-1.name' => 'required|string',
            'zero.0.name' => 'required|string',
            'leadingZero.01.name' => 'required|string',
            'stringPlus.+1.name' => 'required|string',
            'stringNegativeZero.-0.name' => 'required|string',
            'stringNegativeLeadingZero.-01.name' => 'required|string',
        ];
    }
}

function testNumericSegments(NumericSegmentsRequest $request): void
{
    assertType('mixed', $request->negative);
    assertType('mixed', $request->zero);
    assertType('mixed', $request->leadingZero);
    assertType('array|null', $request->validated('negative'));
    assertType('array|null', $request->validated('zero'));
    assertType('array|null', $request->validated('leadingZero'));
    assertType("array{'+1': array{name: string}}", $request->validated('stringPlus'));
    assertType("array{'-0': array{name: string}}", $request->validated('stringNegativeZero'));
    assertType("array{'-01': array{name: string}}", $request->validated('stringNegativeLeadingZero'));
}
