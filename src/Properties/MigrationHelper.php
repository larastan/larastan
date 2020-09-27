<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Properties;

use Iterator;
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

    public function __construct(CachedParser $parser, ?string $databaseMigrationPath)
    {
        $this->parser = $parser;
        $this->databaseMigrationPath = $databaseMigrationPath;
    }

    /**
     * @return array<string, SchemaTable>
     */
    public function initializeTables(): array
    {
        if ($this->databaseMigrationPath === null) {
            $this->databaseMigrationPath = database_path().'/migrations';
        }

        if (! is_dir($this->databaseMigrationPath)) {
            return [];
        }

        $schemaAggregator = new SchemaAggregator();
        $files = $this->getMigrationFiles($this->databaseMigrationPath);
        $filesArray = iterator_to_array($files);
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
     * @return Iterator<SplFileInfo>
     */
    private function getMigrationFiles(string $path): Iterator
    {
        return new RegexIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)), '/\.php$/i');
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
}
