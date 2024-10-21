<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use Larastan\Larastan\Internal\FileHelper;
use Larastan\Larastan\Properties\Schema\PhpMyAdminDataTypeToPhpTypeConverter;
use PhpMyAdmin\SqlParser\Components\CreateDefinition;
use PhpMyAdmin\SqlParser\Exceptions\ParserException;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;

use function array_filter;
use function array_key_exists;
use function database_path;
use function file_get_contents;
use function is_array;
use function ksort;

final class SquashedMigrationHelper
{
    /** @param  string[] $schemaPaths */
    public function __construct(
        private array $schemaPaths,
        private FileHelper $fileHelper,
        private PhpMyAdminDataTypeToPhpTypeConverter $converter,
        private bool $disableSchemaScan,
    ) {
    }

    /** @return SchemaTable[] */
    public function initializeTables(): array
    {
        if ($this->disableSchemaScan) {
            return [];
        }

        if (empty($this->schemaPaths)) {
            $this->schemaPaths = [database_path('schema')];
        }

        $filesArray = $this->fileHelper->getFiles($this->schemaPaths, '/\.dump|\.sql/i');

        if (empty($filesArray)) {
            return [];
        }

        ksort($filesArray);

        /** @var array<string, SchemaTable> $tables */
        $tables = [];

        foreach ($filesArray as $file) {
            $fileContents = file_get_contents($file->getPathname());

            if ($fileContents === false) {
                continue;
            }

            try {
                $parser = new Parser($fileContents);
            } catch (ParserException) {
                // TODO: re-throw the exception with a clear message?
                continue;
            }

            /** @var CreateStatement[] $createStatements */
            $createStatements = array_filter($parser->statements, static fn (Statement $statement) => $statement instanceof CreateStatement && $statement->name !== null);

            foreach ($createStatements as $createStatement) {
                if ($createStatement->name?->table === null || array_key_exists($createStatement->name->table, $tables)) {
                    continue;
                }

                $table = new SchemaTable($createStatement->name->table);

                if (! is_array($createStatement->fields)) {
                    continue;
                }

                foreach ($createStatement->fields as $field) {
                    if ($field->name === null || $field->type === null) {
                        continue;
                    }

                    $table->setColumn(new SchemaColumn($field->name, $this->converter->convert($field->type), $this->isNullable($field)));
                }

                $tables[$createStatement->name->table] = $table;
            }
        }

        return $tables;
    }

    private function isNullable(CreateDefinition $definition): bool
    {
        return ! $definition->options?->has('NOT NULL');
    }
}
