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
