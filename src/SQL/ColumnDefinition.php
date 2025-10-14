<?php

declare(strict_types=1);

namespace Larastan\Larastan\SQL;

final class ColumnDefinition
{
    public function __construct(
        public string $name,
        public string $type,
        public bool $nullable,
    ) {
    }
}
