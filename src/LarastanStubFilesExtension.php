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
    public function __construct(private bool $checkFormRequestTypes = false)
    {
    }

    /** @inheritDoc */
    public function getFiles(): array
    {
        $files = [];
        $roots = [__DIR__ . '/../stubs'];

        if ($this->checkFormRequestTypes) {
            // Feature stubs replace the regular ones that share their relative path.
            $roots[] = __DIR__ . '/../stubs/formRequest';
        }

        foreach ($roots as $root) {
            $stubDirectories = Finder::create()->directories()->name('/^\d+/')->in($root)->depth(0);

            // Include only applicable versions
            $stubDirectories
                ->filter(static fn (SplFileInfo $directory) => version_compare($directory->getFilename(), LARAVEL_VERSION, '<='))
                ->sort(static fn (SplFileInfo $a, SplFileInfo $b) => version_compare($a->getFilename(), $b->getFilename()));

            $stubDirs = [$root . '/common', ...array_keys(iterator_to_array($stubDirectories))];

            $stubFiles = Finder::create()->files()->name('*.stub')->in($stubDirs);

            foreach ($stubFiles as $stubFile) {
                $files[$stubFile->getRelativePathname()] = $stubFile->getRealPath();
            }
        }

        return array_values($files);
    }
}
