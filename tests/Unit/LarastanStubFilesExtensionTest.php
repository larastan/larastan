<?php

declare(strict_types=1);

namespace Tests\Unit;

use Larastan\Larastan\LarastanStubFilesExtension;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

use function defined;

class LarastanStubFilesExtensionTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testGetFilesDoesNotFailWhenLaravelVersionIsUndefined(): void
    {
        $this->assertFalse(defined('LARAVEL_VERSION'));

        $files = (new LarastanStubFilesExtension())->getFiles();

        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $this->assertFileExists($file);
            $this->assertStringEndsWith('.stub', $file);
        }
    }
}
