<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use Larastan\Larastan\SQL\SqlParser;
use Larastan\Larastan\Support\RecursiveDirectoryIterator;
use PHPStan\File\FileHelper;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ReflectionProvider;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

use function count;
use function database_path;
use function is_dir;
use function iterator_to_array;
use function uasort;

class MigrationHelper
{
    public function __construct(
        private Parser $parser,
        /** @var string[] */
        private array $databaseMigrationPath,
        private FileHelper $fileHelper,
        private bool $disableMigrationScan,
        private ReflectionProvider $reflectionProvider,
        private InitializerExprTypeResolver $initializerExprTypeResolver,
        private SqlParser $sqlParser,
    ) {
    }

    /**
     * @param array<string, SchemaTable> $tables
     *
     * @return array<string, SchemaTable>
     */
    public function initializeTables(array $tables = []): array
    {
        if ($this->disableMigrationScan) {
            return $tables;
        }

        $schemaAggregator = new SchemaAggregator(
            $this->reflectionProvider,
            $this->initializerExprTypeResolver,
            $this->sqlParser,
            $tables,
        );
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

    /** @return SplFileInfo[] */
    public function getMigrationFiles(): array
    {
        /** @var SplFileInfo[] $migrationFiles */
        $migrationFiles = [];

        foreach ($this->getMigrationDirectories() as $directory) {
            if (! is_dir($directory)) {
                continue;
            }

            $migrationFiles += iterator_to_array(
                new RegexIterator(
                    new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)),
                    '/\.php$/i',
                ),
            );
        }

        return $migrationFiles;
    }

    /**
     * The directories scanned for migrations, see DirectoryResolver::resolve().
     *
     * @return list<string>
     */
    public function getMigrationDirectories(): array
    {
        if ($this->disableMigrationScan) {
            return [];
        }

        if (count($this->databaseMigrationPath) === 0) {
            $this->databaseMigrationPath = [database_path('migrations')];
        }

        return DirectoryResolver::resolve($this->databaseMigrationPath, $this->fileHelper);
    }
}
