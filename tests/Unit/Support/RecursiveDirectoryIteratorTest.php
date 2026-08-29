<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use Larastan\Larastan\Support\RecursiveDirectoryIterator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
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
use function sprintf;
use function stream_wrapper_register;
use function stream_wrapper_unregister;
use function sys_get_temp_dir;
use function unlink;

use const DIRECTORY_SEPARATOR;

class RecursiveDirectoryIteratorTest extends TestCase
{
    private string $directory;

    public function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'larastan-directory-iterator-' . bin2hex(random_bytes(8));

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
    }

    public function tearDown(): void
    {
        $this->removeDirectory($this->directory);
    }

    /** @return array<string, SplFileInfo> */
    private function scan(string $directory): array
    {
        /** @var array<string, SplFileInfo> $files */
        $files = iterator_to_array(
            new RegexIterator(
                new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)),
                '/\.php$/i',
            ),
        );

        return $files;
    }

    #[Test]
    public function it_finds_the_same_files_as_the_plain_spl_construct(): void
    {
        $files = $this->scan($this->directory);

        /** @var array<string, SplFileInfo> $splFiles */
        $splFiles = iterator_to_array(
            new RegexIterator(
                new RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->directory)),
                '/\.php$/i',
            ),
        );

        self::assertSame(array_keys($splFiles), array_keys($files));
        self::assertContainsOnlyInstancesOf(SplFileInfo::class, $files);
    }

    #[Test]
    public function it_finds_files_in_nested_directories(): void
    {
        $files = $this->scan($this->directory);

        self::assertCount(43, $files);
        self::assertArrayHasKey($this->directory . DIRECTORY_SEPARATOR . 'UPPERCASE.PHP', $files);
        self::assertArrayHasKey($this->directory . DIRECTORY_SEPARATOR . 'sub' . DIRECTORY_SEPARATOR . 'nested.php', $files);
        self::assertArrayHasKey(
            $this->directory . DIRECTORY_SEPARATOR . 'sub' . DIRECTORY_SEPARATOR . 'deeper' . DIRECTORY_SEPARATOR . 'deep.php',
            $files,
        );
    }

    #[Test]
    public function it_rewinds_normally_once_iteration_has_advanced(): void
    {
        // Only the redundant first rewind() is skipped. After next() has
        // run, rewind() must really seek back, or reusing an iterator
        // would silently continue where the last pass stopped.
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->directory));

        $first  = array_keys(iterator_to_array($iterator));
        $second = array_keys(iterator_to_array($iterator));

        self::assertSame($first, $second);
        self::assertNotSame([], $first);
    }

    #[Test]
    public function it_hands_out_children_that_skip_their_own_first_rewind(): void
    {
        $iterator = new RecursiveDirectoryIterator($this->directory);

        $children = null;
        for ($iterator->rewind(); $iterator->valid(); $iterator->next()) {
            if (! $iterator->hasChildren()) {
                continue;
            }

            $children = $iterator->getChildren();
            break;
        }

        // Each child is constructed fresh, so its own first rewind() is
        // skipped by the same rule — a subdirectory's first buffered chunk
        // must not be lost either.
        self::assertInstanceOf(RecursiveDirectoryIterator::class, $children);
    }

    #[Test]
    public function it_does_not_rewind_a_newly_constructed_directory_stream(): void
    {
        BrokenRewindDirectoryStream::$rewindCalls = 0;

        self::assertTrue(stream_wrapper_register(BrokenRewindDirectoryStream::SCHEME, BrokenRewindDirectoryStream::class));

        try {
            $files = $this->scan(BrokenRewindDirectoryStream::SCHEME . '://root');

            self::assertSame([
                BrokenRewindDirectoryStream::SCHEME . '://root/one.php',
                BrokenRewindDirectoryStream::SCHEME . '://root/two.php',
            ], array_keys($files));
            self::assertSame(0, BrokenRewindDirectoryStream::$rewindCalls);
        } finally {
            stream_wrapper_unregister(BrokenRewindDirectoryStream::SCHEME);
        }
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
