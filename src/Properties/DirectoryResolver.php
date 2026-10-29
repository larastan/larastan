<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use PHPStan\File\FileHelper;

use function array_values;
use function glob;
use function is_dir;
use function rtrim;
use function strpbrk;

use const GLOB_ONLYDIR;

/**
 * Turns the configured migration and schema paths into the directories to scan and watch.
 *
 * @internal
 */
final class DirectoryResolver
{
    /**
     * An existing directory is taken literally, so glob characters in its path are not expanded.
     * A path without glob characters is kept even if it does not exist, so that creating it can be
     * noticed. A glob that matches no directory resolves to nothing.
     *
     * @param string[] $paths
     *
     * @return list<string>
     */
    public static function resolve(array $paths, FileHelper $fileHelper): array
    {
        $directories = [];

        foreach ($paths as $path) {
            $path = $fileHelper->absolutizePath($path);

            $matches = is_dir($path) || strpbrk($path, '*?[') === false
                ? [$path]
                : (glob($path, GLOB_ONLYDIR) ?: []);

            foreach ($matches as $directory) {
                $directory               = rtrim($fileHelper->normalizePath($directory), '/\\');
                $directories[$directory] = $directory;
            }
        }

        return array_values($directories);
    }
}
