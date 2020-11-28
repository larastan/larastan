<?php

namespace Tests;

use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /** @var string */
    private $configPath;

    public function setUp(): void
    {
        parent::setUp();

        @File::makeDirectory(dirname(__DIR__).'/vendor/nunomaduro/larastan', 0755, true);
        @File::copy(dirname(__DIR__).'/bootstrap.php', dirname(__DIR__).'/vendor/nunomaduro/larastan/bootstrap.php');
        File::copyDirectory(__DIR__.'/Application/database/migrations', $this->getBasePath().'/database/migrations');
        File::copyDirectory(__DIR__.'/Application/config', $this->getBasePath().'/config');
        File::copyDirectory(__DIR__.'/Application/resources', $this->getBasePath().'/resources');

        $this->configPath = __DIR__.'/phpstan-tests.neon';
    }

    public function execLarastan(string $filename)
    {
        $command = escapeshellcmd(dirname(__DIR__).'/vendor/phpstan/phpstan/phpstan');

        exec(
            sprintf(
                '%s %s analyse --no-progress --level=max --error-format=%s --configuration=%s %s',
                escapeshellarg(PHP_BINARY),
                $command,
                'json',
                escapeshellarg($this->configPath),
                escapeshellarg($filename)
            ),
            $jsonResult
        );

        return json_decode($jsonResult[0], true);
    }

    /**
     * @param string $configPath
     *
     * @return static
     */
    public function setConfigPath(string $configPath): self
    {
        $this->configPath = $configPath;

        return $this;
    }
}
