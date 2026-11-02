<?php

declare(strict_types=1);

namespace Larastan\Larastan\SQL;

final class TableDefinition
{
    /** @param list<ColumnDefinition> $columns */
    public function __construct(
        public string $name,
        public array $columns,
    ) {
    }
}
