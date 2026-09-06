<?php

declare(strict_types=1);

namespace ValidationRulesLaravel12_22;

use App\Http\Requests\StrictValidationRequest;
use App\Http\Requests\RequestPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use function PHPStan\Testing\assertType;

final class StrictInRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'numericValue' => 'required|numeric:strict|in:1',
            'numericObjectValue' => ['required', 'numeric:strict', Rule::in([1])],
            'booleanValue' => 'required|boolean:strict|in:0,1',
            'integerValue' => 'required|integer:strict|in:-1,0,1',
            'decimalIntegerValue' => 'required|integer:strict|in:1.0',
            'noncanonicalIntegerValue' => 'required|integer:strict|in:+1',
        ];
    }
}

final class StrictEnumRequest extends FormRequest
{
    public function rules(): array
    {
        return ['priority' => ['required', 'integer:strict', Rule::enum(RequestPriority::class)]];
    }
}

function test(StrictValidationRequest $request, StrictInRequest $inRequest, StrictEnumRequest $enumRequest): void
{
    assertType('(1|2)', $enumRequest->priority);
    assertType('bool', $request->booleanValue);
    assertType('float|int', $request->numericValue);
    assertType('int', $request->integerValue);
    assertType('0|1', $request->integerInValue);
    assertType('int<1, 20>|null', $request->boundedInteger);
    assertType('int<10, 15>|null', $request->repeatedBounds);
    assertType('3|null', $request->exactInteger);
    assertType('2|3|null', $request->constrainedInteger);
    assertType('int|null', $request->contradictoryBounds);

    assertType('float|int', $inRequest->numericValue);
    assertType('float|int', $inRequest->numericObjectValue);
    assertType('bool', $inRequest->booleanValue);
    assertType('-1|0|1', $inRequest->integerValue);
    assertType('int', $inRequest->decimalIntegerValue);
    assertType('int', $inRequest->noncanonicalIntegerValue);
    assertType(
        'array{numericValue: float|int, numericObjectValue: float|int, booleanValue: bool, integerValue: -1|0|1, decimalIntegerValue: int, noncanonicalIntegerValue: int}',
        $inRequest->validated(),
    );
}
