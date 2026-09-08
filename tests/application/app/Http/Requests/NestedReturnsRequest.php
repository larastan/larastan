<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NestedReturnsRequest extends FormRequest
{
    public function rules(): array
    {
        $closure = static function (): array {
            return ['closure' => 'required|string'];
        };

        function nestedFormRequestRules(): array
        {
            return ['function' => 'required|string'];
        }

        $helper = new class {
            public function rules(): array
            {
                return ['nestedClass' => 'required|integer'];
            }
        };

        return ['actual' => 'required|string'];
    }
}
