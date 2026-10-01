<?php

declare(strict_types=1);

namespace App\ValueObjects;

class InvokableDefault
{
    public function __invoke(): int
    {
        return 42;
    }
}
