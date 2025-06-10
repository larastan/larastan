<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use iamcal\SQLParser;
use iamcal\SQLParserSyntaxException;
use Larastan\Larastan\Properties\Schema\MySqlDataTypeToPhpTypeConverter;
use PHPStan\File\FileHelper;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

use function array_key_exists;
use function database_path;
use function file_get_contents;
use function glob;
use function is_array;
use function is_bool;
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
                $parser                      = new SQLParser();
                $parser->throw_on_bad_syntax = true; // @phpcs:ignore
                $tableDefinitions            = $parser->parse($fileContents);
            } catch (SQLParserSyntaxException) {
                // TODO: re-throw the exception with a clear message?
                continue;
            }

            foreach ($tableDefinitions as $definition) {
                if (array_key_exists($definition['name'], $tables)) {
                    continue;
                }

                if (! is_array($definition['fields'])) {
                    continue;
                }

                $table = new SchemaTable($definition['name']);
                foreach ($definition['fields'] as $field) {
                    if ($field['name'] === null || $field['type'] === null) {
                        continue;
                    }

                    $table->setColumn(new SchemaColumn(
                        $field['name'],
                        $this->converter->convert($field['type']),
                        $this->isNullable($field),
                    ));
                }

                $tables[$definition['name']] = $table;
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

    /** @param  array<string, string|bool|null> $definition */
    private function isNullable(array $definition): bool
    {
        if (! array_key_exists('null', $definition)) {
            return false;
        }

        if (is_bool($definition['null'])) {
            return $definition['null'];
        }

        return false;
    }
}
