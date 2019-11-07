<?php

declare(strict_types=1);

namespace Tests;

use Orchestra\Testbench\TestCase;
use Composer\Autoload\ClassMapGenerator;

class ExtensionsTest extends TestCase
{
    use RegisteredExtensions;

    public function testExtensionsAreRegistered(): void
    {
        $loadedExtensions = $this->getLoadedExtensions();
        $registeredExtensions = $this->getRegisteredExtensions();

        $extensions = array_diff($loadedExtensions, $registeredExtensions);

        $this->assertCount(
            0,
            $extensions,
            'Extension ('.implode(', ', $extensions).') is not registered in extension.neon'
        );
    }

    private function getLoadedExtensions(): array
    {
        $baseDir = __DIR__.'/../src';
        $extensions = [];

        foreach (ClassMapGenerator::createMap($baseDir) as $model => $path) {
            if (strpos($model, 'Extension') !== false) {
                $extensions[] = $model;
            }
        }

        return $extensions;
    }
}
