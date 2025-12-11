<?php

declare(strict_types=1);

namespace Larastan\Larastan\SQL;

final class ColumnDefinition
{
    /** @param list<lowercase-string> $typeOptions */
    public function __construct(
        public string $name,
        public string $type,
        public array $typeOptions,
        public bool $nullable,
    ) {
    }
}
