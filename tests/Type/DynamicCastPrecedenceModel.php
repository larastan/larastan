<?php

declare(strict_types=1);

namespace Tests\Type;

use Illuminate\Database\Eloquent\Model;

final class DynamicCastPrecedenceModel extends Model
{
    /**
     * Laravel declares $casts without a native type, which cannot be narrowed.
     *
     * @var array<string, string>
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $casts = ['integer' => 'bool'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['integer' => $this->selectCast(), 'created_at' => 'string', 'id' => 'string'];
    }

    private function selectCast(): string
    {
        return 'string';
    }
}
