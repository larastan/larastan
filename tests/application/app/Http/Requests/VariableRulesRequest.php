<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

const GLOBAL_RULE = 'integer';

class VariableRulesRequest extends FormRequest
{
    private const MAX = 20;

    private const RULE = 'string';

    public function rules(): array
    {
        $localRule = 'integer';

        $rules = [
            'title' => 'required|' . self::RULE,
            'quantity' => ['required', $localRule],
            'maximum' => ['required', 'integer', 'max:' . self::MAX],
            'global' => 'required|' . GLOBAL_RULE,
        ];

        return $rules;
    }
}
