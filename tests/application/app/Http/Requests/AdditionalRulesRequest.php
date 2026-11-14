<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class AdditionalRulesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'requiredZero' => ['required', 'string', 'in:0'],
            'digitsValue' => 'required|digits:2',
            'digitsBetweenValue' => 'required|digits_between:1,2',
            'decimalMaximumValue' => 'required|decimal:0|max:2',
            'multipleOfValue' => 'required|multiple_of:0.5',
            'alphaNumericValue' => 'required|alpha_num',
            'startsWithValue' => 'required|starts_with:4',
            'plainDate' => ['sometimes', 'nullable', 'date'],
            'ipValue' => 'required|ip',
            'macAddressValue' => 'required|mac_address',
            'jsonValue' => 'required|json',
            'listSizeValue' => 'required|size:2|list',
            'boundedNumericInteger' => ['required', Rule::numeric()->integer()->max(10)->min(2)],
            'integerGreaterThanValue' => 'required|integer|gt:5',
            'integerLessThanValue' => 'required|integer|lt:5',
            'integerComparisonBoundsValue' => 'required|integer|gte:5|lte:10',
            'numericLessThanValue' => 'required|numeric|lt:-5',
            'fieldComparisonValue' => 'required|integer|gt:integerSizeValue',
            'quotedInValue' => 'required|string|in:"foo,bar",baz',
            'numericInValue' => 'required|numeric|in:1,2',
            'numericObjectInValue' => ['required', 'numeric', Rule::in([1, 2])],
            'booleanInValue' => 'required|boolean|in:0,1',
            'mixedNumericInValue' => 'required|in:1',
            'mixedEmptyInValue' => 'present|in:""',
            'stringNumericInValue' => 'required|string|in:1,2',
            'stringMixedInValue' => 'required|string|in:1,draft',
            'textInValue' => 'required|in:date,rating',
            'declinedValue' => 'declined',
            'nullableAcceptedValue' => 'nullable|accepted',
            'formattedDate' => ['required', Rule::date()->format('Y-m-d')],
            'emailValue' => ['required', Rule::email()],
            'dimensionsValue' => ['required', Rule::dimensions()->maxWidth(1920)],
            'passwordValue' => ['required', Password::min(8)->letters()->numbers()],
        ];
    }
}
