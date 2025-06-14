<?php

declare(strict_types=1);

namespace Larastan\Larastan\Internal;

use PHPStan\File\FileHelper as PHPStanFileHelper;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

use function array_push;
use function array_reduce;
use function array_values;
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
     * @return list<non-empty-string>
     */
    public function getDirectories(array $directories): array
    {
        return array_values(array_reduce(
            $directories,
            function (array $carry, string $path): array {
                $normalPath   = $this->fileHelper->normalizePath($path);
                $absolutePath = $this->fileHelper->absolutizePath($normalPath);

                if ($this->isGlobPattern($absolutePath)) {
                    $glob = glob($absolutePath, GLOB_ONLYDIR);

                    if ($glob === false) {
                        return $carry;
                    }

                    array_push($carry, ...$glob);
                } else {
                    if (! is_dir($absolutePath)) {
                        return $carry;
                    }

                    $carry[] = $absolutePath;
                }

                return $carry;
            },
            [],
        ));
    }

    /**
     * @param  array<array-key, string> $directories
     *
     * @return array<non-empty-string, SplFileInfo>
     */
    public function getFiles(array $directories, string|null $filter = null): array
    {
        return array_reduce(
            $this->getDirectories($directories),
            static function (array $carry, string $directory) use ($filter): array {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($directory),
                );

                if (is_string($filter)) {
                    $iterator = new RegexIterator($iterator, $filter);
                }

                $carry += iterator_to_array($iterator);

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
