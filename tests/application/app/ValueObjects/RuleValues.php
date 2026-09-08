<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;

/** @implements Arrayable<int, int|string> */
final class RuleValues implements Arrayable
{
    /** @return array{1, 'foo'} */
    public function toArray(): array
    {
        return [1, 'foo'];
    }
}
