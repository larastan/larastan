<?php

declare(strict_types=1);

namespace Larastan\Larastan\SQL;

use RuntimeException;
use Throwable;

final class SqlParserFailure extends RuntimeException
{
    public static function create(string $message, Throwable|null $previous = null): self
    {
        return new self($message, previous: $previous);
    }
}
