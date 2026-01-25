<?php

declare(strict_types=1);

namespace Larastan\Larastan\SQL;

final class ColumnDefinition
{
    /**
     * @param list<lowercase-string> $typeOptions
     * @param list<string>           $values
     */
    public function __construct(
        public string $name,
        public string $type,
        public array $typeOptions,
        public bool $nullable,
        public array $values = [],
    ) {
    }
}
