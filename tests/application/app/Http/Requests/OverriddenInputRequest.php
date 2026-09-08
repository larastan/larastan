<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OverriddenInputRequest extends FormRequest
{
    public function rules(): array
    {
        return ['name' => 'required|string'];
    }

    public function input($key = null, $default = null): mixed
    {
        return parent::input($key, $default);
    }

    public function integer($key, $default = 0): int
    {
        return parent::integer($key, $default);
    }
}
