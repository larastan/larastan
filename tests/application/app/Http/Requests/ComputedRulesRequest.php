<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ComputedRulesRequest extends FormRequest
{
    public function rules(): array
    {
        $constantKey = 'constantKey';
        $dynamicKey  = $this->method();
        $dynamicRule = 'required|' . $this->string('rule');
        $ternaryRule = $this->isMethod('POST') ? 'required|string' : 'required|integer';
        $coalesceRule = $this->input('rule') ?? 'required|string';

        return [
            $dynamicKey => 'required|string',
            $constantKey => 'required|string',
            'dynamicConcatenation' => $dynamicRule,
            'ternary' => $ternaryRule,
            'coalesce' => $coalesceRule,
            'stableComputedSibling' => 'required|integer',
        ];
    }
}
