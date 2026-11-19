<?php

declare(strict_types=1);

namespace App;

use BadMethodCallException;
use Illuminate\Support\Traits\Macroable;

/** A third-party style class that consults its macros from its own __call. */
class MacroableWithOwnCall
{
    use Macroable {
        __call as macroCall;
    }

    /** @param array<mixed> $parameters */
    public function __call(string $method, array $parameters): mixed
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        throw new BadMethodCallException();
    }
}
