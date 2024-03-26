<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use PHPStan\File\FileHelper;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;
use RecursiveDirectoryIterator;
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
    ) {
    }

    public function parseMigrations(ModelDatabaseHelper &$modelDatabaseHelper): void
    {
        if ($this->disableMigrationScan) {
            return;
        }

        if (count($this->databaseMigrationPath) === 0) {
            $this->databaseMigrationPath = [database_path('migrations')];
        }

        $schemaAggregator = new SchemaAggregator($modelDatabaseHelper);
        $filesArray       = $this->getMigrationFiles();

        if (empty($filesArray)) {
            return;
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
    }

    /** @return SplFileInfo[] */
    private function getMigrationFiles(): array
    {
        /** @var SplFileInfo[] $migrationFiles */
        $migrationFiles = [];

        foreach ($this->databaseMigrationPath as $additionalPath) {
            $absolutePath = $this->fileHelper->absolutizePath($additionalPath);

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
