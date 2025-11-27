<?php

declare(strict_types=1);

namespace App;

use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;

class Importer
{
    use Macroable;

    /** @var bool */
    public $isImported;

    public function isImported(): bool
    {
        return random_int(0, 1) > 0;
    }

    public function import(): bool
    {
        return random_int(0, 1) > 0;
    }

    public function getKey(): string
    {
        return Str::random(5);
    }
}
