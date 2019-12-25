<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Finder\Finder;

class FeaturesTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        @File::makeDirectory(__DIR__.'/../vendor/nunomaduro/larastan', 0755, true);
        @File::makeDirectory(__DIR__.'/../vendor/nunomaduro/larastan/config', 0755, true);
        @File::copy(__DIR__.'/../bootstrap.php', __DIR__.'/../vendor/nunomaduro/larastan/bootstrap.php');
        @File::copy(__DIR__.'/../config/mixins.php', __DIR__.'/../vendor/nunomaduro/larastan/config/mixins.php');
        @File::copy(__DIR__.'/../config/statics.php', __DIR__.'/../vendor/nunomaduro/larastan/config/statics.php');
    }

    public function getFeatures(): array
    {
        $calls = [];
        $baseDir = __DIR__.DIRECTORY_SEPARATOR.'Features'.DIRECTORY_SEPARATOR;

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ((new Finder())->in($baseDir)->files() as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $fullPath = realpath((string) $file);
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
        $configPath = __DIR__.'/../extension.neon';
        $command = escapeshellcmd(__DIR__.'/../vendor/bin/phpstan');

        exec(sprintf('%s %s analyse --no-progress  --level=max --configuration %s --autoload-file %s %s --error-format=%s',
            escapeshellarg(PHP_BINARY), $command,
            escapeshellarg($configPath),
            escapeshellarg(__DIR__.'/../vendor/autoload.php'),
            escapeshellarg($file),
            'json'),
            $jsonResult);

        $result = json_decode($jsonResult[0], true);

        if ($result['totals']['errors'] > 0 || $result['totals']['file_errors'] > 0) {
            $this->fail($jsonResult[0]);
        }

        return 0;
    }
}
