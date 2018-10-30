<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Finder\Finder;
use Illuminate\Contracts\Console\Kernel;
use NunoMaduro\Larastan\LarastanServiceProvider;

class FeaturesTest extends TestCase
{
    private $kernel;

    public function setUp(): void
    {
        parent::setUp();
        $app = $this->createApplication();
        (new LarastanServiceProvider($app))->register();

        $this->kernel = $app->make(Kernel::class);
    }

    public function getFeatures(): array
    {
        $calls = [];
        $baseDir = __DIR__.'/Features/';

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ((new Finder())->in($baseDir)->files() as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $fullPath = (string) $file;
            $calls[str_replace($baseDir, '', $fullPath)] = [$fullPath];
        }

        return $calls;
    }

    /**
     * @dataProvider getFeatures
     */
    public function testFeatures(string $file): void
    {
        if ($this->analyze($file) !== 0) {
            $msg = sprintf('Larastan detected errors in: %s', $file);
            $this->fail($msg);
        }

        $this->assertTrue(true);
    }

    private function analyze(string $file): int
    {
        return $this->kernel->call('code:analyse', [
            '--level' => 'max',
            '--paths' => $file,
            '--bin-path' => __DIR__.'/../vendor/bin',
            '--autoload-file' => __DIR__.'/../vendor/autoload.php',
            '--no-tty' => true,
            '--error-format' => 'raw',
        ]);
    }
}
