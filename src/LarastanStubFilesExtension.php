<?php

declare(strict_types=1);

namespace Larastan\Larastan;

use PHPStan\PhpDoc\StubFilesExtension;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

use function array_keys;
use function array_values;
use function iterator_to_array;
use function version_compare;

final class LarastanStubFilesExtension implements StubFilesExtension
{
    /** @inheritDoc */
    public function getFiles(): array
    {
        $stubDirectories = Finder::create()->directories()->name('/^\d+/')->in(__DIR__ . '/../stubs')->depth(0);

        // Include only applicable versions
        $stubDirectories
            ->filter(static fn (SplFileInfo $file) => version_compare($file->getFilename(), LARAVEL_VERSION, '<='))
            ->sort(static fn (SplFileInfo $a, SplFileInfo $b) => version_compare($a->getFilename(), $b->getFilename()));

        $files = [];

        $stubDirs = [__DIR__ . '/../stubs/common', ...array_keys(iterator_to_array($stubDirectories))];

        $stubFiles = Finder::create()->files()->name('*.stub')->in($stubDirs);

        foreach ($stubFiles as $stubFile) {
            $files[$stubFile->getRelativePathname()] = $stubFile->getRealPath();
        }

        return array_values($files);
    }
}
