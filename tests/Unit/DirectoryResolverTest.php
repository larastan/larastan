<?php

declare(strict_types=1);

namespace Tests\Unit;

use Larastan\Larastan\Properties\DirectoryResolver;
use PHPStan\File\FileHelper;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function touch;
use function uniqid;
use function unlink;

#[CoversClass(DirectoryResolver::class)]
class DirectoryResolverTest extends PHPStanTestCase
{
    private FileHelper $fileHelper;

    private string $root;

    public function setUp(): void
    {
        $this->fileHelper = self::getContainer()->getByType(FileHelper::class);
        $this->root       = sys_get_temp_dir() . '/larastan-directory-resolver-' . uniqid();

        mkdir($this->root);
        mkdir($this->root . '/a');
        mkdir($this->root . '/b');
        mkdir($this->root . '/[literal]');
        touch($this->root . '/c.php');
    }

    public function tearDown(): void
    {
        unlink($this->root . '/c.php');
        rmdir($this->root . '/[literal]');
        rmdir($this->root . '/b');
        rmdir($this->root . '/a');
        rmdir($this->root);
    }

    #[Test]
    public function it_keeps_a_path_without_glob_characters_even_if_it_does_not_exist(): void
    {
        self::assertSame(
            [$this->root . '/a', $this->root . '/missing'],
            DirectoryResolver::resolve([$this->root . '/a', $this->root . '/missing'], $this->fileHelper),
        );
    }

    #[Test]
    public function it_expands_a_glob_to_the_directories_it_matches(): void
    {
        self::assertSame(
            [$this->root . '/[literal]', $this->root . '/a', $this->root . '/b'],
            DirectoryResolver::resolve([$this->root . '/*'], $this->fileHelper),
        );
    }

    #[Test]
    public function it_drops_a_glob_that_matches_no_directory(): void
    {
        self::assertSame([], DirectoryResolver::resolve([$this->root . '/missing-*', $this->root . '/c.*'], $this->fileHelper));
    }

    #[Test]
    public function it_takes_an_existing_directory_with_glob_characters_literally(): void
    {
        self::assertSame(
            [$this->root . '/[literal]'],
            DirectoryResolver::resolve([$this->root . '/[literal]'], $this->fileHelper),
        );
    }

    #[Test]
    public function it_lists_a_directory_once(): void
    {
        self::assertSame(
            [$this->root . '/a', $this->root . '/b'],
            DirectoryResolver::resolve([$this->root . '/a', $this->root . '/[ab]', $this->root . '/a/'], $this->fileHelper),
        );
    }
}
