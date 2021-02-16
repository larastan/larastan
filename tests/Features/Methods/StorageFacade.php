<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class StorageFacade
{
    public function testDisk(): Filesystem
    {
        return Storage::disk();
    }

    public function testDiskGetDriver(): bool
    {
        return Storage::disk()->deleteDirectory('foo');
    }

    /** @return string|false */
    public function testPutFile()
    {
        return Storage::putFile('foo', 'foo/bar');
    }
}
