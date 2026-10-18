<?php

declare(strict_types=1);

namespace Tests\Rules\Data;

use Exception;

class ThrowIfException extends Exception
{
    public function __construct(string $foo, int $bar)
    {
        parent::__construct($foo, $bar);
    }
}

class ThrowIfExceptionWithOptionalParameter extends Exception
{
    public function __construct(string $foo, int|null $bar = null)
    {
        parent::__construct($foo, $bar ?? 0);
    }
}

abstract class AbstractThrowIfException extends Exception
{
    public function __construct(string $foo)
    {
        parent::__construct($foo);
    }
}

class ThrowIfNotAnException
{
    public function __construct(string $foo)
    {
    }
}
