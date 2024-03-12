<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

/** @see https://github.com/psalm/laravel-psalm-plugin/blob/master/src/SchemaTable.php */
final class SchemaConnection
{
    /** @var array<string, SchemaTable> */
    public array $tables = [];

    public function __construct(public string $name)
    {
    }

    public function setTable(SchemaTable $table): void
    {
        $this->tables[$table->name] = $table;
    }

    public function renameTable(string $oldName, string $newName): void
    {
        if (! isset($this->tables[$oldName])) {
            return;
        }

        $oldTable = $this->tables[$oldName];

        unset($this->tables[$oldName]);

        $oldTable->name = $newName;

        $this->tables[$newName] = $oldTable;
    }

    public function dropTable(string $tableName): void
    {
        unset($this->tables[$tableName]);
    }
}
