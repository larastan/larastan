<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Stringable;

final class StringableValue implements Stringable
{
    public function __toString(): string
    {
        return 'foo';
    }
}
