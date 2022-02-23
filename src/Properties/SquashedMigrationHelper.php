<?php

namespace NunoMaduro\Larastan\Properties;

use NunoMaduro\Larastan\Properties\Schema\PhpMyAdminDataTypeToPhpTypeConverter;
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

final class SquashedMigrationHelper
{
    /**
     * @param  string[]  $schemaPaths
     */
    public function __construct(
        private array $schemaPaths,
        private FileHelper $fileHelper,
        private PhpMyAdminDataTypeToPhpTypeConverter $converter,
    ) {
    }

    /** @return SchemaTable[] */
    public function initializeTables(): array
    {
        if (empty($this->schemaPaths)) {
            $this->schemaPaths = [database_path('schema')];
        }

        $filesArray = $this->getSchemaFiles();

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

            $parser = new Parser($fileContents);
            try {
                $parser->parse();
            } catch (ParserException $exception) {
                // TODO: re-throw the exception with a clear message?
                throw $exception;
            }

            /** @var CreateStatement[] $createStatements */
            $createStatements = array_filter($parser->statements, static fn (Statement $statement) => $statement instanceof CreateStatement);

            foreach ($createStatements as $createStatement) {
                $table = new SchemaTable($createStatement->name->table);

                if (! is_array($createStatement->fields)) {
                    continue;
                }

                foreach ($createStatement->fields as $field) {
                    if (! $field instanceof CreateDefinition) {
                        continue;
                    }

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

    /**
     * @return SplFileInfo[]
     */
    private function getSchemaFiles(): array
    {
        /** @var SplFileInfo[] $schemaFiles */
        $schemaFiles = [];

        foreach ($this->schemaPaths as $additionalPath) {
            $absolutePath = $this->fileHelper->absolutizePath($additionalPath);

            if (is_dir($absolutePath)) {
                $schemaFiles += iterator_to_array(
                    new RegexIterator(
                        new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absolutePath)),
                        '/\.dump/i'
                    )
                );
            }
        }

        return $schemaFiles;
    }

    private function isNullable(CreateDefinition $definition): bool
    {
        if ($definition->options->has('NOT NULL')) {
            return false;
        }

        return true;
    }
}
