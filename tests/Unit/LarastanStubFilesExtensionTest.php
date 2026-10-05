<?php

declare(strict_types=1);

namespace Tests\Unit;

use Larastan\Larastan\LarastanStubFilesExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function escapeshellarg;
use function exec;
use function implode;
use function json_decode;
use function sprintf;
use function var_export;

use const PHP_BINARY;

class LarastanStubFilesExtensionTest extends TestCase
{
    #[Test]
    public function it_returns_the_same_stub_files_before_the_bootstrap_files_have_run(): void
    {
        $code = sprintf(
            'require %s; echo json_encode((new %s())->getFiles());',
            var_export(__DIR__ . '/../../vendor/autoload.php', true),
            LarastanStubFilesExtension::class,
        );

        exec(sprintf('%s -r %s 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg($code)), $output, $exitCode);

        self::assertSame(0, $exitCode, implode("\n", $output));
        self::assertSame((new LarastanStubFilesExtension())->getFiles(), json_decode(implode('', $output), true));
    }
}
