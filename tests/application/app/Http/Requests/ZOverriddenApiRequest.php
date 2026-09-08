<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ZOverriddenApiRequest extends FormRequest
{
    public function rules(): array
    {
        return ['wrong' => 'required|integer'];
    }

    /** @return array{custom: bool} */
    public function validated($key = null, $default = null): array
    {
        return ['custom' => true];
    }

    /** @return array{custom: bool} */
    public function safe(?array $keys = null): array
    {
        return ['custom' => true];
    }
}
