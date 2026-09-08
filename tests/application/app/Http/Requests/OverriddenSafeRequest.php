<?php

declare(strict_types=1);

namespace App\Http\Requests;

class OverriddenSafeRequest extends SafeReturnRequest
{
    /** @return array{custom: string} */
    public function safe(?array $keys = null): array
    {
        return ['custom' => 'value'];
    }
}
