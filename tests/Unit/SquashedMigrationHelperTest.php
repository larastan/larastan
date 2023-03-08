<?php

namespace Tests\Unit;

use NunoMaduro\Larastan\Properties\Schema\PhpMyAdminDataTypeToPhpTypeConverter;
use NunoMaduro\Larastan\Properties\SquashedMigrationHelper;
use PHPStan\File\FileHelper;
use PHPStan\Testing\PHPStanTestCase;

/** @covers \NunoMaduro\Larastan\Properties\SquashedMigrationHelper */
class SquashedMigrationHelperTest extends PHPStanTestCase
{
    /** @test */
    public function it_can_parse_schema_dump_for_a_basic_schema(): void
    {
        $schemaParser = new SquashedMigrationHelper(
            [__DIR__.'/data/schema/basic_schema'],
            self::getContainer()->getByType(FileHelper::class),
            new PhpMyAdminDataTypeToPhpTypeConverter(),
            false
        );

        $tables = $schemaParser->initializeTables();

        $this->assertCount(1, $tables);
        $this->assertArrayHasKey('accounts', $tables);
        $this->assertCount(6, $tables['accounts']->columns);
        $this->assertSame(['id', 'name', 'active', 'description', 'created_at', 'updated_at'], array_keys($tables['accounts']->columns));
        $this->assertSame('int', $tables['accounts']->columns['id']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['name']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['active']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['description']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['created_at']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['updated_at']->readableType);
    }

    /** @test */
    public function it_will_ignore_if_table_already_exists_in_parsed_tables_array(): void
    {
        $schemaParser = new SquashedMigrationHelper(
            [__DIR__.'/data/schema/multiple_schemas_for_same_table'],
            self::getContainer()->getByType(FileHelper::class),
            new PhpMyAdminDataTypeToPhpTypeConverter(),
            false,
        );

        $tables = $schemaParser->initializeTables();

        $this->assertCount(1, $tables);
        $this->assertArrayHasKey('accounts', $tables);
        $this->assertCount(6, $tables['accounts']->columns);
        $this->assertSame(['id', 'name', 'active', 'description', 'created_at', 'updated_at'], array_keys($tables['accounts']->columns));
        $this->assertSame('int', $tables['accounts']->columns['id']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['name']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['active']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['description']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['created_at']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['updated_at']->readableType);
    }

    /** @test */
    public function it_can_find_schemas_with_sql_suffix(): void
    {
        $schemaParser = new SquashedMigrationHelper(
            [__DIR__.'/data/schema/basic_schema_with_sql_extension'],
            self::getContainer()->getByType(FileHelper::class),
            new PhpMyAdminDataTypeToPhpTypeConverter(),
            false
        );

        $tables = $schemaParser->initializeTables();

        $this->assertCount(1, $tables);
        $this->assertArrayHasKey('accounts', $tables);
        $this->assertCount(6, $tables['accounts']->columns);
        $this->assertSame(['id', 'name', 'active', 'description', 'created_at', 'updated_at'], array_keys($tables['accounts']->columns));
        $this->assertSame('int', $tables['accounts']->columns['id']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['name']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['active']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['description']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['created_at']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['updated_at']->readableType);
    }

    /** @test */
    public function it_can_find_schemas_with_different_extensions(): void
    {
        $schemaParser = new SquashedMigrationHelper(
            [__DIR__.'/data/schema/multiple_schemas_with_different_extensions'],
            self::getContainer()->getByType(FileHelper::class),
            new PhpMyAdminDataTypeToPhpTypeConverter(),
            false
        );

        $tables = $schemaParser->initializeTables();

        $this->assertCount(2, $tables);
        $this->assertArrayHasKey('accounts', $tables);
        $this->assertCount(6, $tables['accounts']->columns);
        $this->assertSame(['id', 'name', 'active', 'description', 'created_at', 'updated_at'], array_keys($tables['accounts']->columns));
        $this->assertSame('int', $tables['accounts']->columns['id']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['name']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['active']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['description']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['created_at']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['updated_at']->readableType);
        $this->assertArrayHasKey('users', $tables);
        $this->assertCount(6, $tables['users']->columns);
        $this->assertSame(['id', 'name', 'active', 'description', 'created_at', 'updated_at'], array_keys($tables['users']->columns));
        $this->assertSame('int', $tables['users']->columns['id']->readableType);
        $this->assertSame('string', $tables['users']->columns['name']->readableType);
        $this->assertSame('string', $tables['users']->columns['active']->readableType);
        $this->assertSame('string', $tables['users']->columns['description']->readableType);
        $this->assertSame('string', $tables['users']->columns['created_at']->readableType);
        $this->assertSame('string', $tables['users']->columns['updated_at']->readableType);
    }

    /** @test */
    public function it_can_disable_schema_scanning(): void
    {
        $schemaParser = new SquashedMigrationHelper(
            [__DIR__.'/data/schema/multiple_schemas_with_different_extensions'],
            self::getContainer()->getByType(FileHelper::class),
            new PhpMyAdminDataTypeToPhpTypeConverter(),
            true
        );

        $tables = $schemaParser->initializeTables();

        $this->assertSame([], $tables);
    }
}
