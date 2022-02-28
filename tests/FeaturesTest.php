<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FeaturesTest extends BaseTestCase
{
    use ExecutesLarastan;

    public function setUp(): void
    {
        parent::setUp();

        @File::makeDirectory(dirname(__DIR__).'/vendor/nunomaduro/larastan', 0755, true);
        @File::copy(dirname(__DIR__).'/bootstrap.php', dirname(__DIR__).'/vendor/nunomaduro/larastan/bootstrap.php');
        File::copyDirectory(__DIR__.'/Application/database/migrations', $this->getBasePath().'/database/migrations');
        File::copyDirectory(__DIR__.'/Application/database/schema', $this->getBasePath().'/database/schema');
        File::copyDirectory(__DIR__.'/Application/config', $this->getBasePath().'/config');
        File::copyDirectory(__DIR__.'/Application/resources', $this->getBasePath().'/resources');

        $this->configPath = __DIR__.'/phpstan-tests.neon';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        File::deleteDirectory($this->getBasePath().'/database/migrations');
        File::deleteDirectory($this->getBasePath().'/database/schema');
    }

    public function getFeatures(): array
    {
        $calls = [];
        $baseDir = __DIR__.DIRECTORY_SEPARATOR.'Features'.DIRECTORY_SEPARATOR;

        /** @var SplFileInfo $file */
        foreach ((new Finder())->in($baseDir)->files()->name('*.php')->notName('bootstrap.php') as $file) {
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
        $configFile = dirname($file).'/phpstan-tests.neon';
        if (file_exists($configFile)) {
            $this->configPath = $configFile;
        }

        if ($this->analyze($file) === 0) {
            $this->assertTrue(true);
        }
    }
}
