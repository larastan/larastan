<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use SplFileInfo;

use function array_filter;
use function array_map;
use function array_merge;
use function fclose;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function flock;
use function fopen;
use function getmypid;
use function glob;
use function hash;
use function implode;
use function is_array;
use function rename;
use function serialize;
use function sort;
use function sprintf;
use function str_ends_with;
use function unlink;
use function unserialize;

use const LOCK_EX;
use const LOCK_UN;

final class MigrationCache
{
    private const CACHE_PREFIX = 'larastan_migrations_';

    public function __construct(
        private string $cacheDirectory,
        private bool $enabled = false,
    ) {
    }

    /**
     * @param SplFileInfo[]                          $migrationFiles
     * @param SplFileInfo[]                          $schemaFiles
     * @param callable(): array<string, SchemaTable> $callback
     *
     * @return array<string, SchemaTable>
     */
    public function remember(array $migrationFiles, array $schemaFiles, callable $callback): array
    {
        if (! $this->enabled) {
            return $callback();
        }

        $fingerprint = $this->generateFingerprint($migrationFiles, $schemaFiles);
        $cachePath   = $this->getCachePath($fingerprint);

        $cached = file_exists($cachePath) ? $this->readFromCache($cachePath) : null;
        if ($cached !== null) {
            return $cached;
        }

        $lockHandle = $this->acquireExclusiveLock();
        if ($lockHandle === false) {
            return $callback();
        }

        try {
            $cached = file_exists($cachePath) ? $this->readFromCache($cachePath) : null;
            if ($cached !== null) {
                return $cached;
            }

            $tables   = $callback();
            $tempPath = sprintf('%s.tmp.%d', $cachePath, getmypid());

            file_put_contents($tempPath, serialize($tables));
            rename($tempPath, $cachePath);

            $this->cleanupOldCacheFiles($fingerprint);

            return $tables;
        } finally {
            $this->releaseLock($lockHandle);
        }
    }

    /** @return array<string, SchemaTable>|null */
    private function readFromCache(string $path): array|null
    {
        $content = file_get_contents($path);

        if ($content === false) {
            return null;
        }

        /** @var array<string, SchemaTable>|false $data*/
        $data = @unserialize($content);

        if (! is_array($data)) {
            return null;
        }

        return $data;
    }

    private function cleanupOldCacheFiles(string $currentFingerprint): void
    {
        $currentCacheFile = sprintf('%s%s.cache', self::CACHE_PREFIX, $currentFingerprint);
        $files            = glob(sprintf('%s/%s*.cache', $this->cacheDirectory, self::CACHE_PREFIX)) ?: [];

        foreach (array_filter($files, static fn (string $file): bool => ! str_ends_with($file, $currentCacheFile)) as $file) {
            @unlink($file);
        }
    }

    /**
     * @param SplFileInfo[] $migrationFiles
     * @param SplFileInfo[] $schemaFiles
     */
    private function generateFingerprint(array $migrationFiles, array $schemaFiles): string
    {
        $metadata = array_merge(
            array_map(static fn (SplFileInfo $file): string => sprintf('M:%s:%d', $file->getPathname(), $file->getMTime()), $migrationFiles),
            array_map(static fn (SplFileInfo $file): string => sprintf('S:%s:%d', $file->getPathname(), $file->getMTime()), $schemaFiles),
        );

        sort($metadata);

        return hash('xxh128', implode('|', $metadata));
    }

    private function getCachePath(string $fingerprint): string
    {
        return sprintf('%s/%s%s.cache', $this->cacheDirectory, self::CACHE_PREFIX, $fingerprint);
    }

    private function getLockPath(): string
    {
        return sprintf('%s/larastan_migrations.lock', $this->cacheDirectory);
    }

    /** @return resource|false */
    private function acquireExclusiveLock(): mixed
    {
        $handle = @fopen($this->getLockPath(), 'c');

        if ($handle === false) {
            return false;
        }

        if (! flock($handle, LOCK_EX)) {
            fclose($handle);

            return false;
        }

        return $handle;
    }

    /** @param resource $handle */
    private function releaseLock(mixed $handle): void
    {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
