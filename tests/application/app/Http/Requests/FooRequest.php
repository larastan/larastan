<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FooRequest extends FormRequest
{
    public function rules(): array
    {
        $limit = config('app.rule.limit');
        $rule = config('app.rule.rule');

        return [
            'name' => 'required|string',
            'age' => ['required', 'integer', 'min:' . $limit, $rule],
            'newsletter' => 'sometimes|accepted',
        ];
    }
}
