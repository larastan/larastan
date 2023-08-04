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
        $stubDirectories = Finder::create()->directories()->in(__DIR__.'/../stubs')->depth(0);

        // Include only applicable versions
        $stubDirectories->filter(function (SplFileInfo $file) {
            return version_compare($file->getFilename(), LARAVEL_VERSION, '<=');
        });

        // Sort by version
        $stubDirectories->sort(function (SplFileInfo $a, SplFileInfo $b) {
            return version_compare($a->getFilename(), $b->getFilename());
        });

        $files = [];

        foreach ($stubDirectories as $stubDirectory) {
            $stubFiles = Finder::create()->files()->name('*.stub')->in($stubDirectory->getRealPath());

            foreach ($stubFiles as $stubFile) {
                $files[$stubFile->getRelativePathname()] = $stubFile->getRealPath();
            }
        }

        return array_values($files);
    }
}
