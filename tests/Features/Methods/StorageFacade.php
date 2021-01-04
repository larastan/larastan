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

    /**
     * @return \League\Flysystem\FilesystemOperator|\League\Flysystem\FilesystemInterface
     */
    public function testDiskGetDriver()
    {
        return Storage::disk()->getDriver();
    }

    /** @return string|false */
    public function testPutFile()
    {
        return Storage::putFile('foo', 'foo/bar');
    }
}
