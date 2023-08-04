<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan;

use PHPStan\PhpDoc\StubFilesExtension;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

final class LarastanStubFilesExtension implements StubFilesExtension
{
    /**
     * @inheritDoc
     */
    public function getFiles(): array
    {
        // Get list of version directories
        $versions = Finder::create()->directories()->in(__DIR__.'/../stubs')->depth(0);

        // Include only applicable versions
        $versions->filter(function (SplFileInfo $file) {
            return version_compare($file->getFilename(), LARAVEL_VERSION, '<=');
        });

        // Sort by version
        $versions->sort(function (SplFileInfo $a, SplFileInfo $b) {
            return version_compare($a->getFilename(), $b->getFilename());
        });

        //List files from version directories

        $files = [];

        foreach ($versions as $version) {
            $finder = Finder::create()->files()->name('*.stub')->in($version->getRealPath());

            foreach ($finder as $file) {
                $files[$file->getRelativePathname()] = $file->getRealPath();
            }
        }

        return array_values($files);
    }
}
