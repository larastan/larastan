<?php

declare(strict_types=1);

namespace App\Casts;

enum Source: string
{
    case Api = 'api';
    case Import = 'import';
}
