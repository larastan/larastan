<?php

declare(strict_types=1);

namespace App\Http\Requests;

class OverriddenValidatedRequest extends SafeReturnRequest
{
    /** @return array{custom: string} */
    public function validated($key = null, $default = null): array
    {
        return ['custom' => 'value'];
    }
}
