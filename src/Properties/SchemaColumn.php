<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

/** @see https://github.com/psalm/psalm-plugin-laravel/blob/master/src/Handlers/Eloquent/Schema/SchemaColumn.php */
final class SchemaColumn
{
    public string $writeableType;

    public function __construct(
        public string $name,
        public string $readableType,
        public bool $nullable = false,
        /** @var array<int, string> */
        public array|null $options = null,
    ) {
        $this->writeableType = $readableType;
    }
}
