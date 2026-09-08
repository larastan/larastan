<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StrictValidationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'booleanValue' => 'required|boolean:strict',
            'numericValue' => 'required|numeric:strict',
            'integerValue' => 'required|integer:strict',
            'integerInValue' => 'required|integer:strict|in:0,1',
            'boundedInteger' => ['sometimes', 'integer:strict', 'min:1', 'max:20'],
            'repeatedBounds' => ['min:10', 'between:5,15', 'max:20', 'integer:strict'],
            'exactInteger' => 'size:3|integer:strict',
            'constrainedInteger' => 'integer:strict|in:1,2,3|min:2',
            'contradictoryBounds' => 'integer:strict|min:20|max:1',
            'numericInValue' => 'required|numeric:strict|in:1',
            'numericObjectInValue' => ['required', 'numeric:strict', Rule::in([1])],
            'booleanInValue' => 'required|boolean:strict|in:0,1',
            'signedIntegerInValue' => 'required|integer:strict|in:-1,0,1',
            'decimalIntegerValue' => 'required|integer:strict|in:1.0',
            'noncanonicalIntegerValue' => 'required|integer:strict|in:+1',
            'priority' => ['required', 'integer:strict', Rule::enum(RequestPriority::class)],
        ];
    }
}
