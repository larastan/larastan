<?php

namespace NunoMaduro\Larastan\Properties;

use PhpMyAdmin\SqlParser\Components\CreateDefinition;
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
     * @param string[] $schemaPaths
     */
    public function __construct(
        private array $schemaPaths,
        private FileHelper $fileHelper
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
            $parser = new Parser(file_get_contents($file->getPathname()));
            $parser->parse();

            /** @var CreateStatement[] $createStatements */
            $createStatements = array_filter($parser->statements, fn(Statement $statement) => $statement instanceof CreateStatement);

            foreach ($createStatements as $createStatement) {
                $table = new SchemaTable($createStatement->name->table);

                foreach ($createStatement->fields as $field) {
                    if (! $field instanceof CreateDefinition) {
                        continue;
                    }

                    if ($field->name === null) {
                        continue;
                    }

                    dd($field);

                    $table->setColumn(new SchemaColumn($field->name));
                }
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
}
