<?php

declare(strict_types=1);

namespace Tests;

use Nette\Neon\Neon;

trait RegisteredExtensions
{
    /** @var string */
    protected $extensionPath = __DIR__ . '/../extension.neon';

    /** @var array|null */
    private $registeredExtensions;

    protected function getRegisteredExtensions(): array
    {
        $extensionFile = file_get_contents($this->extensionPath);
        $extension = Neon::decode($extensionFile);

        if (!$this->registeredExtensions) {
            $this->registeredExtensions = array_map(function ($service) {
                return $service['class'];
            }, $extension['services']);
        }

        return $this->registeredExtensions;
    }
}
