<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RootWildcardWithSiblingRulesRequest extends FormRequest
{
    public function rules(): array
    {
        return ['payload' => 'required|array', '*' => 'exclude'];
    }
}
