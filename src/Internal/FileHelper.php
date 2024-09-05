<?php

declare(strict_types=1);

namespace Larastan\Larastan\Internal;

use PHPStan\File\FileHelper as PHPStanFileHelper;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

use function array_reduce;
use function glob;
use function is_dir;
use function is_string;
use function iterator_to_array;
use function preg_match;

use const GLOB_ONLYDIR;

/** @internal */
final class FileHelper
{
    public function __construct(
        private PHPStanFileHelper $fileHelper,
    ) {
    }

    /**
     * @param  array<array-key, string> $directories
     *
     * @return array<string, SplFileInfo>
     */
    public function getFiles(array $directories, string|null $filter = null): array
    {
        return array_reduce(
            $directories,
            function (array $carry, string $path) use ($filter): array {
                $absolutePath = $this->fileHelper->absolutizePath($path);

                if ($this->isGlobPattern($absolutePath)) {
                    $glob = glob($absolutePath, GLOB_ONLYDIR);

                    if ($glob === false) {
                        return $carry;
                    }

                    $directories = $glob;
                } else {
                    if (! is_dir($absolutePath)) {
                        return $carry;
                    }

                    $directories = [$absolutePath];
                }

                foreach ($directories as $directory) {
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($directory),
                    );

                    if (is_string($filter)) {
                        $iterator = new RegexIterator($iterator, $filter);
                    }

                    $carry += iterator_to_array($iterator);
                }

                return $carry;
            },
            [],
        );
    }

    private function isGlobPattern(string $path): bool
    {
        return preg_match('~[*?[\]]~', $path) > 0;
    }
}
