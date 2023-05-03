<?php

namespace NunoMaduro\Larastan\Properties;

use LogicException;

final class EloquentCast
{
    public function __construct(
        public string $type,
        /** @var list<string> */
        public array $parameters,
    ) {
    }

    public static function fromString(string $string): self
    {
        $parts = explode(':', $string);

        // If the cast is prefixed with `encrypted:` we need to skip to the next
        if (($parts[0] ?? null) === 'encrypted') {
            array_shift($parts);
        }

        if (empty($parts)) {
            throw new LogicException('Invalid cast provided.');
        }

        return new self(
            type: array_shift($parts),
            parameters: $parts,
        );
    }
}
