<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/** @implements CastsAttributes<array{raw: string|null, formatted: string|null}, string|null> */
class FormattedDate implements CastsAttributes
{
    /** @param  array<string, mixed>  $attributes */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        return [
            'raw' => is_string($value) ? $value : null,
            'formatted' => is_string($value) ? date('jS M Y G:i', (int) strtotime($value)) : null,
        ];
    }

    /** @param  array<string, mixed>  $attributes */
    public function set(Model $model, string $key, mixed $value, array $attributes): string|null
    {
        return is_string($value) ? $value : null;
    }
}
