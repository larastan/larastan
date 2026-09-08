<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnpackedRulesRequest extends FormRequest
{
    private const BEFORE = ['spreadOverwritten' => 'required|string'];

    private const AFTER = ['constant' => 'required|string'];

    /** @return array<string, string> */
    private function dynamicRules(): array
    {
        return ['dynamicOnly' => 'required|string', 'parent' => 'exclude', 'spreadOverwritten' => 'required|integer'];
    }

    public function rules(): array
    {
        return [
            'overwritten' => 'required|string',
            ...self::BEFORE,
            ...$this->dynamicRules(),
            ...self::AFTER,
            'stable' => 'required|integer',
            'stableString' => 'required|string',
            'parent.name' => 'required|string',
            'explicitParent' => 'required|array',
            'explicitParent.name' => 'required|string',
            'v1\.0' => 'required|string',
        ];
    }
}
