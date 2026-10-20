<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Custom cast from issue #1744: a date column read back as formatted parts.
 *
 * @implements CastsAttributes<array{raw: string|null, formatted: string|null, ago: string|null}, array{raw: string|null, formatted: string|null, ago: string|null}>
 */
class FormattedDateCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array{raw: string|null, formatted: string|null, ago: string|null}
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        return [
            'raw' => is_string($value) ? $value : null,
            'formatted' => null,
            'ago' => null,
        ];
    }

    /**
     * @param  array{raw: string|null, formatted: string|null, ago: string|null}|null  $value
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (! is_array($value)) {
            return null;
        }

        $raw = $value['raw'] ?? null;

        return is_string($raw) ? $raw : null;
    }
}
