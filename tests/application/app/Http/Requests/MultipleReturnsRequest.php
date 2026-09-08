<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MultipleReturnsRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->isMethod('POST')) {
            return [
                'shared' => 'required|string',
                'different' => 'required|integer',
                'firstOnly' => 'required|string',
                'payload' => ['required', Rule::array(['name'])],
                'record' => ['required', Rule::array(['name'])],
                'record.name' => 'required|string',
                'pruned' => ['required', 'array', Rule::array(['name', 'other'])],
                'pruned.name' => 'string',
                'parent' => 'exclude',
                'parent.name' => 'required|string',
            ];
        }

        return [
            'shared' => ['required', 'string'],
            'different' => 'required|string',
            'secondOnly' => 'required|string',
            'payload' => ['required', Rule::array(['name'])],
            'record' => ['required', Rule::array(['name'])],
            'record.name' => 'required|string',
            'pruned' => ['required', Rule::array(['name', 'other'])],
            'pruned.name' => 'string',
            'parent.name' => 'required|string',
        ];
    }
}
