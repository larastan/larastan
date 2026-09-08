<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BroadPhpDocSpreadRequest extends FormRequest
{
    /** @return array<string, mixed> */
    private function commonRules(): array
    {
        return ['broadOnly' => 'required|string'];
    }

    public function rules(): array
    {
        return [
            ...$this->commonRules(),
            'stable' => 'required|string',
        ];
    }
}
