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

        @File::makeDirectory(__DIR__.'/../vendor/nunomaduro/larastan', 0755, true);
        @File::makeDirectory(__DIR__.'/../vendor/nunomaduro/larastan/config', 0755, true);
        @File::copy(__DIR__.'/../bootstrap.php', __DIR__.'/../vendor/nunomaduro/larastan/bootstrap.php');
        @File::copy(__DIR__.'/../config/mixins.php', __DIR__.'/../vendor/nunomaduro/larastan/config/mixins.php');
        @File::copy(__DIR__.'/../config/statics.php', __DIR__.'/../vendor/nunomaduro/larastan/config/statics.php');
        File::copyDirectory(__DIR__.'/Application/database/migrations', $this->getBasePath().'/database/migrations');
        File::copyDirectory(__DIR__.'/Application/config', $this->getBasePath().'/config');
        File::copyDirectory(__DIR__.'/Application/resources', $this->getBasePath().'/resources');

        $this->configPath = __DIR__.'/../extension.neon';
    }

    public function execLarastan(string $filename)
    {
        $command = escapeshellcmd(__DIR__.'/../vendor/bin/phpstan');

        exec(sprintf('%s %s analyse --no-progress  --level=max --configuration %s  %s --error-format=%s',
            escapeshellarg(PHP_BINARY), $command,
            escapeshellarg($this->configPath),
            escapeshellarg($filename),
            'json'),
            $jsonResult);

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
