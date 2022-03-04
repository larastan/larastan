<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Properties;

use PHPStan\File\FileHelper;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

class MigrationHelper
{
    /** @var Parser */
    private $parser;

    /** @var string[] */
    private $databaseMigrationPath;

    /** @var FileHelper */
    private $fileHelper;

    /**
     * @param  string[]  $databaseMigrationPath
     */
    public function __construct(Parser $parser, array $databaseMigrationPath, FileHelper $fileHelper)
    {
        $this->parser = $parser;
        $this->databaseMigrationPath = $databaseMigrationPath;
        $this->fileHelper = $fileHelper;
    }

    /**
     * @param  array<string, SchemaTable>  $tables
     * @return array<string, SchemaTable>
     */
    public function initializeTables(array $tables = []): array
    {
        if (count($this->databaseMigrationPath) === 0) {
            $this->databaseMigrationPath = [database_path('migrations')];
        }

        $schemaAggregator = new SchemaAggregator($tables);
        $filesArray = $this->getMigrationFiles();

        if (empty($filesArray)) {
            return $tables;
        }

        ksort($filesArray);

        $this->requireFiles($filesArray);

        foreach ($filesArray as $file) {
            try {
                $schemaAggregator->addStatements($this->parser->parseFile($file->getPathname()));
            } catch (ParserErrorsException $e) {
                continue;
            }
        }

        return $schemaAggregator->tables;
    }

    /**
     * @return SplFileInfo[]
     */
    private function getMigrationFiles(): array
    {
        /** @var SplFileInfo[] $migrationFiles */
        $migrationFiles = [];

        foreach ($this->databaseMigrationPath as $additionalPath) {
            $absolutePath = $this->fileHelper->absolutizePath($additionalPath);

            if (is_dir($absolutePath)) {
                $migrationFiles += iterator_to_array(
                    new RegexIterator(
                        new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absolutePath)),
                        '/\.php$/i'
                    )
                );
            }
        }

        return $migrationFiles;
    }

    /**
     * @param  SplFileInfo[]  $files
     */
    private function requireFiles(array $files): void
    {
        foreach ($files as $file) {
            require_once $file;
        }
    }
}
