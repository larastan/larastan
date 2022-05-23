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

    public function testDrive(): Filesystem
    {
        return Storage::drive();
    }

    public function testDiskGetDriver(): bool
    {
        return Storage::disk()->deleteDirectory('foo');
    }

    public function testDriveGetDriver(): bool
    {
        return Storage::drive()->deleteDirectory('foo');
    }

    /** @return string|false */
    public function testPutFile()
    {
        return Storage::putFile('foo', 'foo/bar');
    }
}
