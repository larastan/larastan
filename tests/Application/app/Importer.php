<?php

declare(strict_types=1);

namespace App;

use Illuminate\Support\Str;

class Importer
{
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
