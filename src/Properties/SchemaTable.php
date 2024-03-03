<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

/** @see https://github.com/psalm/laravel-psalm-plugin/blob/master/src/SchemaTable.php */
final class SchemaTable
{
    /** @var array<string, SchemaColumn> */
    public array $columns = [];

    public function __construct(public string $name)
    {
    }

    public function setColumn(SchemaColumn $column): void
    {
        $this->columns[$column->name] = $column;
    }

    public function renameColumn(string $oldName, string $newName): void
    {
        if (! isset($this->columns[$oldName])) {
            return;
        }

        $oldColumn = $this->columns[$oldName];

        unset($this->columns[$oldName]);

        $oldColumn->name = $newName;

        $this->columns[$newName] = $oldColumn;
    }

    public function dropColumn(string $columnName): void
    {
        unset($this->columns[$columnName]);
    }
}
