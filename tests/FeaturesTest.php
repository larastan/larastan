<?php

declare(strict_types=1);

namespace Tests;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;
use Illuminate\Contracts\Console\Kernel;
use NunoMaduro\Larastan\LarastanServiceProvider;
use Symfony\Component\Console\Output\BufferedOutput;

class FeaturesTest extends TestCase
{
    private $kernel;

    public function setUp(): void
    {
        parent::setUp();

        $app = $this->createApplication();

        (new LarastanServiceProvider($app))->register();

        $this->kernel = $app->make(Kernel::class);

        @File::makeDirectory(__DIR__.'/../vendor/nunomaduro/larastan', 0755, true);
        @File::makeDirectory(__DIR__.'/../vendor/nunomaduro/larastan/config', 0755, true);
        @File::copy(__DIR__.'/../bootstrap.php', __DIR__.'/../vendor/nunomaduro/larastan/bootstrap.php');
        @File::copy(__DIR__.'/../config/mixins.php', __DIR__.'/../vendor/nunomaduro/larastan/config/mixins.php');
        @File::copy(__DIR__.'/../config/statics.php', __DIR__.'/../vendor/nunomaduro/larastan/config/statics.php');
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
        if ($this->analyze($file) === 0) {
            $this->assertTrue(true);
        }
    }

    private function analyze(string $file): int
    {
        $result = $this->kernel->call('code:analyse', [
            '--level' => 'max',
            '--paths' => $file,
            '--bin-path' => __DIR__.'/../vendor/bin',
            '--autoload-file' => __DIR__.'/../vendor/autoload.php',
            '--error-format' => 'raw',
            '--no-tty' => true,
            '--no-progress' => true,
        ], $output = new BufferedOutput);

        if ($result !== 0) {
            $this->fail($output->fetch());
        }

        return $result;
    }
}
