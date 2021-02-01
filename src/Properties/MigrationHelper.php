<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Properties;

use PHPStan\File\FileHelper;
use PHPStan\Parser\CachedParser;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

class MigrationHelper
{
    /** @var CachedParser */
    private $parser;

    /** @var ?string */
    private $databaseMigrationPath;

    /** @var string */
    private $currentWorkingDirectory;

    /**
     * @var string[]
     */
    private $additionalDatabaseMigrationPaths;

    /**
     * @param string[] $additionalDatabaseMigrationPaths
     */
    public function __construct(
        CachedParser $parser,
        string $currentWorkingDirectory,
        ?string $databaseMigrationPath,
        array $additionalDatabaseMigrationPaths = []
    ) {
        $this->parser = $parser;
        $this->databaseMigrationPath = $databaseMigrationPath;
        $this->currentWorkingDirectory = $currentWorkingDirectory;
        $this->additionalDatabaseMigrationPaths = $additionalDatabaseMigrationPaths;
    }

    /**
     * @return array<string, SchemaTable>
     */
    public function initializeTables(): array
    {
        if ($this->databaseMigrationPath !== null) {
            $this->databaseMigrationPath = $this->getFileHelper()->absolutizePath($this->databaseMigrationPath);
        } else {
            $this->databaseMigrationPath = database_path('migrations');
        }

        if (! is_dir($this->databaseMigrationPath)) {
            return [];
        }

        $schemaAggregator = new SchemaAggregator();
        $filesArray = $this->getMigrationFiles($this->databaseMigrationPath);
        ksort($filesArray);

        $this->requireFiles($filesArray);

        foreach ($filesArray as $file) {
            $schemaAggregator->addStatements($this->parser->parseFile($file->getPathname()));
        }

        return $schemaAggregator->tables;
    }

    /**
     * @param string $path
     *
     * @return SplFileInfo[]
     */
    private function getMigrationFiles(string $path): array
    {
        $migrationFiles = iterator_to_array(
            new RegexIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)), '/\.php$/i')
        );

        foreach ($this->additionalDatabaseMigrationPaths as $additionalPath) {
            $migrationFiles += iterator_to_array(
                new RegexIterator(
                    new RecursiveIteratorIterator(new RecursiveDirectoryIterator($additionalPath)),
                    '/\.php$/i'
                )
            );
        }

        return $migrationFiles;
    }

    /**
     * @param SplFileInfo[] $files
     */
    private function requireFiles(array $files): void
    {
        foreach ($files as $file) {
            require_once $file;
        }
    }

    private function getFileHelper(): FileHelper
    {
        return new FileHelper($this->currentWorkingDirectory);
    }
}
