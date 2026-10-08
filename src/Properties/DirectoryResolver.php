<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use PHPStan\File\FileHelper;

use function array_values;
use function glob;
use function is_dir;
use function strpbrk;

/** @internal */
final class DirectoryResolver
{
    /**
     * Expands configured paths, which may be globs, into absolute directories. An existing
     * directory or a path without glob characters is kept as is, even if it does not exist yet;
     * callers check is_dir() before scanning.
     *
     * @param string[] $paths
     *
     * @return list<string>
     */
    public static function resolve(array $paths, FileHelper $fileHelper): array
    {
        $directories = [];

        foreach ($paths as $path) {
            $absolutePath = $fileHelper->absolutizePath($path);

            if (is_dir($absolutePath) || strpbrk($path, '*?[') === false) {
                $directories[$absolutePath] = $absolutePath;

                continue;
            }

            foreach ((glob($path) ?: []) as $matchedPath) {
                $absolutePath = $fileHelper->absolutizePath($matchedPath);

                if (! is_dir($absolutePath)) {
                    continue;
                }

                $directories[$absolutePath] = $absolutePath;
            }
        }

        return array_values($directories);
    }
}
