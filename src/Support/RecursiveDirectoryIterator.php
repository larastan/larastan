<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

/**
 * A \RecursiveDirectoryIterator that skips the redundant first rewind().
 *
 * SPL directory iterators read a first buffer of entries when the handle is
 * constructed, and iteration then opens with a rewind(). On filesystems
 * whose rewinddir() silently ignores a partially read handle — the 9p
 * mounts used by WSL2 and Docker Desktop on Windows
 * (https://github.com/microsoft/WSL/issues/5074) — that rewind does not
 * seek back but the already-buffered chunk is discarded anyway, so files
 * silently go missing from the scan.
 *
 * A freshly constructed iterator is already positioned at the first entry,
 * so that first rewind() has nothing to do and skipping it is safe on every
 * filesystem. next() restores normal rewind behaviour, so an iterator that
 * is reused still starts over from the top. Children created by
 * getChildren() are instances of this class and skip their own first
 * rewind() the same way.
 *
 * This is the approach symfony/finder takes for non-rewindable streams —
 * see Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator.
 */
final class RecursiveDirectoryIterator extends \RecursiveDirectoryIterator
{
    private bool $ignoreFirstRewind = true;

    public function next(): void
    {
        $this->ignoreFirstRewind = false;

        parent::next();
    }

    public function rewind(): void
    {
        if ($this->ignoreFirstRewind) {
            $this->ignoreFirstRewind = false;

            return;
        }

        parent::rewind();
    }
}
