<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use SplFileInfo;

use function closedir;
use function is_dir;
use function opendir;
use function preg_match;
use function readdir;
use function rtrim;

use const DIRECTORY_SEPARATOR;

/**
 * Recursively finds files matching a pattern without relying on SPL directory iterators.
 *
 * SPL iterators (RecursiveDirectoryIterator et al.) read the first directory
 * entry on construction and rewind the handle when iteration starts. On
 * filesystems whose rewinddir() does not reliably seek back after a partial
 * read — notably the 9p mounts used by WSL2 and Docker Desktop on Windows —
 * that rewind is silently ignored and the already-buffered chunk of entries
 * is lost, so files go missing from the scan without any error
 * (https://github.com/microsoft/WSL/issues/5074).
 *
 * A plain readdir() walk never rewinds a partially read handle and therefore
 * sees every entry. On healthy filesystems it finds exactly the same files.
 */
final class DirectoryScanner
{
    /**
     * @param non-empty-string $pattern Regular expression matched against the full pathname.
     *
     * @return array<string, SplFileInfo> Matching files, keyed by pathname.
     */
    public static function findFiles(string $directory, string $pattern): array
    {
        $directory = rtrim($directory, '/\\');

        if ($directory === '') {
            $directory = DIRECTORY_SEPARATOR;
        }

        $files  = [];
        $handle = @opendir($directory);

        if ($handle === false) {
            return $files;
        }

        // Like the SPL iterators, join with exactly one separator regardless
        // of how the incoming path was spelled.
        $prefix = $directory === DIRECTORY_SEPARATOR ? $directory : $directory . DIRECTORY_SEPARATOR;

        $subdirectories = [];

        while (($entry = readdir($handle)) !== false) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $prefix . $entry;

            if (is_dir($path)) {
                $subdirectories[] = $path;
                continue;
            }

            if (preg_match($pattern, $path) !== 1) {
                continue;
            }

            $files[$path] = new SplFileInfo($path);
        }

        closedir($handle);

        foreach ($subdirectories as $subdirectory) {
            $files += self::findFiles($subdirectory, $pattern);
        }

        return $files;
    }
}
