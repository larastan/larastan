<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CollectionSelectionRequest extends FormRequest
{
    public function rules(): array
    {
        return collect([
            'selected' => 'required|string',
            'discarded' => 'required|integer',
        ])->only(['selected'])->all();
    }
}
