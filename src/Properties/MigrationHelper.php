<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use Larastan\Larastan\Support\RecursiveDirectoryIterator;
use PHPStan\File\FileHelper;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ReflectionProvider;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

use function database_path;
use function is_dir;
use function iterator_to_array;
use function uasort;

class MigrationHelper
{
    /** @var list<string>|null */
    private array|null $directories = null;

    public function __construct(
        private Parser $parser,
        /** @var string[] */
        private array $databaseMigrationPath,
        private FileHelper $fileHelper,
        private bool $disableMigrationScan,
        private ReflectionProvider $reflectionProvider,
        private InitializerExprTypeResolver $initializerExprTypeResolver,
    ) {
    }

    /**
     * @param array<string, SchemaTable> $tables
     *
     * @return array<string, SchemaTable>
     */
    public function initializeTables(array $tables = []): array
    {
        $schemaAggregator = new SchemaAggregator($this->reflectionProvider, $this->initializerExprTypeResolver, $tables);
        $filesArray       = $this->getMigrationFiles();

        if (empty($filesArray)) {
            return $tables;
        }

        uasort($filesArray, static function (SplFileInfo $a, SplFileInfo $b) {
            return $a->getFilename() <=> $b->getFilename();
        });

        foreach ($filesArray as $file) {
            try {
                $schemaAggregator->addStatements($this->parser->parseFile($file->getPathname()));
            } catch (ParserErrorsException) {
                continue;
            }
        }

        return $schemaAggregator->tables;
    }

    /** @return list<string> */
    public function getMigrationDirectories(): array
    {
        if ($this->disableMigrationScan) {
            return [];
        }

        return $this->directories ??= DirectoryResolver::resolve($this->databaseMigrationPath ?: [database_path('migrations')], $this->fileHelper);
    }

    /** @return SplFileInfo[] */
    public function getMigrationFiles(): array
    {
        /** @var SplFileInfo[] $migrationFiles */
        $migrationFiles = [];

        foreach ($this->getMigrationDirectories() as $absolutePath) {
            if (! is_dir($absolutePath)) {
                continue;
            }

            $migrationFiles += iterator_to_array(
                new RegexIterator(
                    new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absolutePath)),
                    '/\.php$/i',
                ),
            );
        }

        return $migrationFiles;
    }
}
