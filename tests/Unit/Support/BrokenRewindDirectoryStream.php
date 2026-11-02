<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

final class BrokenRewindDirectoryStream
{
    public const SCHEME = 'larastan-broken-directory';

    public mixed $context;

    public static int $rewindCalls = 0;

    /** @var list<string> */
    private array $entries = ['.', '..', 'one.php', 'two.php', 'notes.txt'];

    private int $position = 0;

    public function dir_opendir(string $path, int $options): bool
    {
        $this->position = 0;

        return true;
    }

    public function dir_readdir(): string|false
    {
        return $this->entries[$this->position++] ?? false;
    }

    public function dir_rewinddir(): bool
    {
        self::$rewindCalls++;

        return true;
    }

    public function dir_closedir(): bool
    {
        return true;
    }

    /** @return array<int|string, int> */
    public function url_stat(string $path, int $flags): array
    {
        $mode = $path === self::SCHEME . '://root' ? 0040777 : 0100666;

        return [
            0 => 0,
            1 => 0,
            2 => $mode,
            3 => 1,
            4 => 0,
            5 => 0,
            6 => 0,
            7 => 0,
            8 => 0,
            9 => 0,
            10 => 0,
            11 => -1,
            12 => -1,
            'dev' => 0,
            'ino' => 0,
            'mode' => $mode,
            'nlink' => 1,
            'uid' => 0,
            'gid' => 0,
            'rdev' => 0,
            'size' => 0,
            'atime' => 0,
            'mtime' => 0,
            'ctime' => 0,
            'blksize' => -1,
            'blocks' => -1,
        ];
    }
}
