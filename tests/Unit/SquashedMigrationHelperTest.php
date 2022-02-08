<?php

namespace Tests\Unit;

use NunoMaduro\Larastan\Properties\SquashedMigrationHelper;
use PHPStan\File\FileHelper;
use PHPStan\Testing\PHPStanTestCase;

/** @covers \NunoMaduro\Larastan\Properties\SquashedMigrationHelper */
class SquashedMigrationHelperTest extends PHPStanTestCase
{
    /** @test */
    function it_can_parse_schema_dump()
    {
        $schemaParser = new SquashedMigrationHelper([__DIR__ . '/data/schema'], self::getContainer()->getByType(FileHelper::class));

        $schemaParser->initializeTables();
    }
}
