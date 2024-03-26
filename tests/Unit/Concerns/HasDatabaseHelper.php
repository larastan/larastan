<?php

declare(strict_types=1);

namespace Tests\Unit\Concerns;

use Larastan\Larastan\Properties\MigrationHelper;
use Larastan\Larastan\Properties\ModelDatabaseHelper;
use Larastan\Larastan\Properties\Schema\PhpMyAdminDataTypeToPhpTypeConverter;
use Larastan\Larastan\Properties\SquashedMigrationHelper;
use PHPStan\File\FileHelper;
use PHPStan\Testing\PHPStanTestCase;

/** @mixin PHPStanTestCase */
trait HasDatabaseHelper
{
    private string $defaultConnection;
    private ModelDatabaseHelper $modelDatabaseHelper;

    public function setUp(): void
    {
        $this->modelDatabaseHelper = new ModelDatabaseHelper(
            $this->createReflectionProvider(),
            $this->getSquashedMigrationHelper(),
            $this->getMigrationHelper(),
        );

        $this->defaultConnection = $this->modelDatabaseHelper->getDefaultConnection();
    }

    /** @param  string[] $dirs */
    private function getMigrationHelper(array $dirs = ['foo'], bool $disableScan = false): MigrationHelper
    {
        return new MigrationHelper(
            self::getContainer()->getService('currentPhpVersionSimpleDirectParser'),
            $dirs,
            self::getContainer()->getByType(FileHelper::class),
            $disableScan,
        );
    }

    /** @param  string[] $dirs */
    private function getSquashedMigrationHelper(array $dirs = ['foo'], bool $disableScan = false): SquashedMigrationHelper
    {
        return new SquashedMigrationHelper(
            $dirs,
            self::getContainer()->getByType(FileHelper::class),
            new PhpMyAdminDataTypeToPhpTypeConverter(),
            $disableScan,
        );
    }
}
