<?php

namespace Tests;

use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        @File::makeDirectory(__DIR__.'/../vendor/nunomaduro/larastan', 0755, true);
        @File::makeDirectory(__DIR__.'/../vendor/nunomaduro/larastan/config', 0755, true);
        @File::copy(__DIR__.'/../bootstrap.php', __DIR__.'/../vendor/nunomaduro/larastan/bootstrap.php');
        @File::copy(__DIR__.'/../config/mixins.php', __DIR__.'/../vendor/nunomaduro/larastan/config/mixins.php');
        @File::copy(__DIR__.'/../config/statics.php', __DIR__.'/../vendor/nunomaduro/larastan/config/statics.php');
        File::copyDirectory(__DIR__.'/Application/database/migrations', $this->getBasePath().'/database/migrations');
    }

    public function execLarastan(string $filename)
    {
        $configPath = __DIR__.'/../extension.neon';
        $command = escapeshellcmd(__DIR__.'/../vendor/bin/phpstan');

        exec(sprintf('%s %s analyse --no-progress  --level=max --configuration %s  %s --error-format=%s',
            escapeshellarg(PHP_BINARY), $command,
            escapeshellarg($configPath),
            escapeshellarg($filename),
            'json'),
            $jsonResult);

        return json_decode($jsonResult[0], true);
    }
}
