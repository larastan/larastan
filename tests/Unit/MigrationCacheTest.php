<?php

declare(strict_types=1);

namespace Tests\Unit;

use Larastan\Larastan\Properties\MigrationCache;
use Larastan\Larastan\Properties\SchemaTable;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

use function array_map;
use function clearstatcache;
use function glob;
use function is_dir;
use function mkdir;
use function rmdir;
use function sleep;
use function sys_get_temp_dir;
use function touch;
use function uniqid;

class MigrationCacheTest extends TestCase
{
    private string $cacheDir;
    private MigrationCache $cache;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/larastan_tests_' . uniqid();
        if (! is_dir($this->cacheDir)) {
            mkdir($this->cacheDir);
        }

        $this->cache = new MigrationCache($this->cacheDir, true); // enabled = true
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->cacheDir . '/*'));
        rmdir($this->cacheDir);
    }

    public function testRememberExecutesCallbackOnFirstCall(): void
    {
        $migrationFiles = [new SplFileInfo($this->createTempFile('mig1.php'))];
        $schemaFiles    = [];

        $called = false;
        $result = $this->cache->remember($migrationFiles, $schemaFiles, static function () use (&$called) {
            $called = true;

            return ['users' => new SchemaTable('users')];
        });

        $this->assertTrue($called);
        $this->assertArrayHasKey('users', $result);
        $this->assertEquals('users', $result['users']->name);
    }

    public function testRememberUsesCacheOnSecondCall(): void
    {
        $migrationFiles = [new SplFileInfo($this->createTempFile('mig1.php'))];
        $schemaFiles    = [];

        // First call
        $this->cache->remember($migrationFiles, $schemaFiles, static function () {
            return ['users' => new SchemaTable('users')];
        });

        // Second call
        $called = false;
        $result = $this->cache->remember($migrationFiles, $schemaFiles, static function () use (&$called) {
            $called = true;

            return [];
        });

        $this->assertFalse($called, 'Callback should not be called on cache hit');
        $this->assertArrayHasKey('users', $result);
    }

    public function testFingerprintChangesOnMtimeChange(): void
    {
        $file1          = $this->createTempFile('mig1.php');
        $migrationFiles = [new SplFileInfo($file1)];
        $schemaFiles    = [];

        // First call
        $this->cache->remember($migrationFiles, $schemaFiles, static function () {
            return ['users' => new SchemaTable('users')];
        });

        // Modify mtime
        sleep(1);
        touch($file1);
        clearstatcache(true, $file1);

        // Second call should execute callback
        $called = false;
        $result = $this->cache->remember($migrationFiles, $schemaFiles, static function () use (&$called) {
            $called = true;

            return ['posts' => new SchemaTable('posts')];
        });

        $this->assertTrue($called, 'Callback should be called when file changed');
        $this->assertArrayHasKey('posts', $result);
        $this->assertArrayNotHasKey('users', $result);
    }

    public function testDisabledCacheAlwaysExecutesCallback(): void
    {
        $cache          = new MigrationCache($this->cacheDir, false); // enabled = false
        $migrationFiles = [new SplFileInfo($this->createTempFile('mig1.php'))];

        // First call
        $cache->remember($migrationFiles, [], static function () {
            return ['users' => new SchemaTable('users')];
        });

        // Second call
        $called = false;
        $cache->remember($migrationFiles, [], static function () use (&$called) {
            $called = true;

            return ['users' => new SchemaTable('users')];
        });

        $this->assertTrue($called, 'Callback should be called when cache is disabled');
    }

    public function testOldCacheFilesAreCleanedUp(): void
    {
        $file1          = $this->createTempFile('mig1.php');
        $migrationFiles = [new SplFileInfo($file1)];
        $schemaFiles    = [];

        // Generate cache for state 1
        $this->cache->remember($migrationFiles, $schemaFiles, static function () {
            return ['v1' => new SchemaTable('v1')];
        });

        // Check cache file exists
        $files = glob($this->cacheDir . '/larastan_migrations_*.cache');
        $this->assertCount(1, $files);
        $firstCacheFile = $files[0];

        // Modify mtime to change fingerprint
        sleep(1);
        touch($file1);
        clearstatcache(true, $file1);

        // Generate cache for state 2
        $this->cache->remember($migrationFiles, $schemaFiles, static function () {
            return ['v2' => new SchemaTable('v2')];
        });

        // Check new cache file exists and old one is gone
        $files = glob($this->cacheDir . '/larastan_migrations_*.cache');
        $this->assertCount(1, $files, 'Should only be one cache file');
        $this->assertNotEquals($firstCacheFile, $files[0], 'New cache file should have different name');
    }

    private function createTempFile(string $name): string
    {
        $path = $this->cacheDir . '/' . $name;
        touch($path);

        return $path;
    }
}
