<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OptionalAncestorRulesRequest extends FormRequest
{
    /** @return array{parent?: 'exclude', 'parent.name': 'required|string', stable: 'required|string'} */
    private function additionalRules(): array
    {
        return ['parent' => 'exclude', 'parent.name' => 'required|string', 'stable' => 'required|string'];
    }

    public function rules(): array
    {
        return $this->additionalRules();
    }
}
