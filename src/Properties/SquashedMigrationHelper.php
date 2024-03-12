<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use Larastan\Larastan\Properties\Schema\PhpMyAdminDataTypeToPhpTypeConverter;
use PhpMyAdmin\SqlParser\Components\CreateDefinition;
use PhpMyAdmin\SqlParser\Exceptions\ParserException;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PHPStan\File\FileHelper;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

use function array_filter;
use function array_key_exists;
use function database_path;
use function explode;
use function file_get_contents;
use function is_array;
use function is_dir;
use function iterator_to_array;
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

    public function parseSchemaDumps(ModelDatabaseHelper &$modelDatabaseHelper): void
    {
        if ($this->disableSchemaScan) {
            return;
        }

        if (empty($this->schemaPaths)) {
            $this->schemaPaths = [database_path('schema')];
        }

        $filesArray = $this->getSchemaFiles();

        if (empty($filesArray)) {
            return;
        }

        ksort($filesArray);

        foreach ($filesArray as $file) {
            // Laravel generates schema files with the format `connectionName-schema.{sql,dump}`
            // If the file name does not match the expected format, then we just use the
            // file name as the connection name.
            $baseName       = explode('.', $file->getBasename())[0];
            $connectionName = explode('-schema', $baseName)[0];
            $connection     = new SchemaConnection($connectionName);

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
                if ($createStatement->name?->table === null || array_key_exists($createStatement->name->table, $connection->tables)) {
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

                $connection->setTable($table);
            }

            $modelDatabaseHelper->setConnection($connection);
        }
    }

    /** @return SplFileInfo[] */
    private function getSchemaFiles(): array
    {
        /** @var SplFileInfo[] $schemaFiles */
        $schemaFiles = [];

        foreach ($this->schemaPaths as $additionalPath) {
            $absolutePath = $this->fileHelper->absolutizePath($additionalPath);

            if (! is_dir($absolutePath)) {
                continue;
            }

            $schemaFiles += iterator_to_array(
                new RegexIterator(
                    new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absolutePath)),
                    '/\.dump|\.sql/i',
                ),
            );
        }

        return $schemaFiles;
    }

    private function isNullable(CreateDefinition $definition): bool
    {
        return ! $definition->options?->has('NOT NULL');
    }
}
