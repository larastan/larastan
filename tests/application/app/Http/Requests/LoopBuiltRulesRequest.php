<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoopBuiltRulesRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [];

        foreach ($this->array('fields') as $field) {
            $rules[$field] = 'required|string';
        }

        return $rules;
    }
}
