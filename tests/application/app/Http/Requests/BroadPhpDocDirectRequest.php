<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BroadPhpDocDirectRequest extends FormRequest
{
    /** @return array<mixed> */
    private function broadRules(): array
    {
        return [];
    }

    public function rules(): array
    {
        return $this->broadRules();
    }
}
