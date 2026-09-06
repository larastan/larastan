<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        ];
    }
}
