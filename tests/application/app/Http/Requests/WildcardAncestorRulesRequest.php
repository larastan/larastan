<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WildcardAncestorRulesRequest extends FormRequest
{
    /** @return array<'parent.*'|'other', string> */
    private function additionalRules(): array
    {
        return ['parent.*' => 'exclude'];
    }

    public function rules(): array
    {
        return [
            ...$this->additionalRules(),
            'parent.item.name' => 'required|string',
            'unrelated.name' => 'required|string',
        ];
    }
}
