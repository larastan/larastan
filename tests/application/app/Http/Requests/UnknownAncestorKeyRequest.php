<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnknownAncestorKeyRequest extends FormRequest
{
    private function ancestor(): string
    {
        return 'parent';
    }

    public function rules(): array
    {
        return [
            $this->ancestor() => 'exclude',
            'parent.name' => 'required|string',
            'stable' => 'required|string',
        ];
    }
}
