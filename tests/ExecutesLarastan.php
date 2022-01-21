<?php

declare(strict_types=1);

namespace Tests;

trait ExecutesLarastan
{
    /** @var string */
    private $configPath;

    public function execLarastan(string $filename)
    {
        $command = escapeshellcmd(dirname(__DIR__).'/vendor/phpstan/phpstan/phpstan');

        exec(
            sprintf(
                '%s %s analyse --no-progress --memory-limit=-1 --level=max --error-format=%s --configuration=%s %s',
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

    private function analyze(string $file): int
    {
        $result = $this->execLarastan($file);

        if (! $result || $result['totals']['errors'] > 0 || $result['totals']['file_errors'] > 0) {
            $this->fail(json_encode($result, JSON_PRETTY_PRINT));
        }

        return 0;
    }

    /**
     * @param  string  $configPath
     * @return static
     */
    public function setConfigPath(string $configPath): self
    {
        $this->configPath = $configPath;

        return $this;
    }
}
