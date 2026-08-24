<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use Larastan\Larastan\Support\DirectoryScanner;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

use function array_keys;
use function bin2hex;
use function file_put_contents;
use function is_dir;
use function iterator_to_array;
use function mkdir;
use function random_bytes;
use function rmdir;
use function scandir;
use function sort;
use function sprintf;
use function sys_get_temp_dir;
use function unlink;

use const DIRECTORY_SEPARATOR;

class DirectoryScannerTest extends TestCase
{
    private string $directory;

    public function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'larastan-directory-scanner-' . bin2hex(random_bytes(8));

        mkdir($this->directory . '/sub/deeper', 0777, true);
        mkdir($this->directory . '/empty', 0777, true);

        // More entries than one directory read buffers on affected filesystems
        // (~29 entries with typical migration file name lengths), so a dropped
        // first chunk would be visible in the result count.
        for ($i = 0; $i < 40; $i++) {
            file_put_contents(sprintf('%s/2024_01_01_%06d_create_some_table_with_a_long_name.php', $this->directory, $i), '<?php');
        }

        file_put_contents($this->directory . '/UPPERCASE.PHP', '<?php');
        file_put_contents($this->directory . '/notes.txt', 'not a match');
        file_put_contents($this->directory . '/sub/nested.php', '<?php');
        file_put_contents($this->directory . '/sub/deeper/deep.php', '<?php');
        file_put_contents($this->directory . '/sub/schema.sql', 'CREATE TABLE t (id INT);');
    }

    public function tearDown(): void
    {
        $this->removeDirectory($this->directory);
    }

    #[Test]
    public function it_finds_all_matching_files_recursively(): void
    {
        $files = DirectoryScanner::findFiles($this->directory, '/\.php$/i');

        self::assertCount(43, $files);
        self::assertArrayHasKey($this->directory . DIRECTORY_SEPARATOR . 'UPPERCASE.PHP', $files);
        self::assertArrayHasKey($this->directory . DIRECTORY_SEPARATOR . 'sub' . DIRECTORY_SEPARATOR . 'nested.php', $files);
        self::assertArrayHasKey(
            $this->directory . DIRECTORY_SEPARATOR . 'sub' . DIRECTORY_SEPARATOR . 'deeper' . DIRECTORY_SEPARATOR . 'deep.php',
            $files,
        );
        self::assertContainsOnlyInstancesOf(SplFileInfo::class, $files);
    }

    #[Test]
    public function it_matches_the_pattern_against_the_full_pathname(): void
    {
        $files = DirectoryScanner::findFiles($this->directory, '/\.dump|\.sql/i');

        self::assertCount(1, $files);
        self::assertArrayHasKey($this->directory . DIRECTORY_SEPARATOR . 'sub' . DIRECTORY_SEPARATOR . 'schema.sql', $files);
    }

    #[Test]
    public function it_returns_an_empty_array_for_a_missing_directory(): void
    {
        self::assertSame([], DirectoryScanner::findFiles($this->directory . '/does-not-exist', '/\.php$/i'));
    }

    #[Test]
    public function it_ignores_trailing_directory_separators(): void
    {
        $withSeparator    = DirectoryScanner::findFiles($this->directory . DIRECTORY_SEPARATOR, '/\.php$/i');
        $withoutSeparator = DirectoryScanner::findFiles($this->directory, '/\.php$/i');

        self::assertSame(array_keys($withoutSeparator), array_keys($withSeparator));
    }

    #[Test]
    public function it_finds_the_same_files_as_the_spl_iterators_it_replaces(): void
    {
        $files = DirectoryScanner::findFiles($this->directory, '/\.php$/i');

        /** @var array<string, SplFileInfo> $splFiles */
        $splFiles = iterator_to_array(
            new RegexIterator(
                new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->directory)),
                '/\.php$/i',
            ),
        );

        $paths = array_keys($files);
        sort($paths);

        $splPaths = array_keys($splFiles);
        sort($splPaths);

        self::assertSame($splPaths, $paths);
    }

    private function removeDirectory(string $directory): void
    {
        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
