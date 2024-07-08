<?php

declare(strict_types=1);

namespace Tests\Rules\Data;

use Illuminate\Support\Facades\Storage;

class CorrectFilesystem
{
    public function getStorage(): Storage
    {
        return Storage::disk('local');
    }
}