<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @method array{documented: bool} validated($key = null, $default = null)
 * @method array{documented: bool} safe(?array $keys = null)
 */
class ZAnnotatedApiRequest extends FormRequest
{
    public function rules(): array
    {
        return ['wrong' => 'required|integer'];
    }
}
