<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use Larastan\Larastan\Properties\Schema\MySqlDataTypeToPhpTypeConverter;
use Larastan\Larastan\SQL\SqlParser;
use Larastan\Larastan\SQL\SqlParserFailure;
use PHPStan\File\FileHelper;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

use function array_key_exists;
use function database_path;
use function file_get_contents;
use function glob;
use function is_dir;
use function iterator_to_array;
use function ksort;

final class SquashedMigrationHelper
{
    /** @param  string[] $schemaPaths */
    public function __construct(
        private array $schemaPaths,
        private FileHelper $fileHelper,
        private MySqlDataTypeToPhpTypeConverter $converter,
        private SqlParser $sqlParser,
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

            try {
                $tableDefinitions = $this->sqlParser->parseTables($fileContents);
            } catch (SqlParserFailure) {
                // TODO: re-throw the exception with a clear message?
                continue;
            }

            foreach ($tableDefinitions as $definition) {
                if (array_key_exists($definition->name, $tables)) {
                    continue;
                }

                $table = new SchemaTable($definition->name);
                foreach ($definition->columns as $column) {
                    $table->setColumn(new SchemaColumn(
                        $column->name,
                        $this->converter->convert($column->type, $column->typeOptions),
                        $column->nullable,
                    ));
                }

                $tables[$definition->name] = $table;
            }
        }

        return $tables;
    }

    /** @return SplFileInfo[] */
    private function getSchemaFiles(): array
    {
        /** @var SplFileInfo[] $schemaFiles */
        $schemaFiles = [];

        foreach ($this->schemaPaths as $additionalPathGlob) {
            foreach ((glob($additionalPathGlob) ?: []) as $additionalPath) {
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
        }

        return $schemaFiles;
    }
}
