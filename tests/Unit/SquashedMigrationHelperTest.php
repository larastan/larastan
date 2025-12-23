<?php

declare(strict_types=1);

namespace Tests\Unit;

use Larastan\Larastan\Properties\Schema\MySqlDataTypeToPhpTypeConverter;
use Larastan\Larastan\Properties\SquashedMigrationHelper;
use PHPStan\File\FileHelper;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

use function array_keys;

#[CoversClass(SquashedMigrationHelper::class)]
class SquashedMigrationHelperTest extends PHPStanTestCase
{
    #[Test]
    public function it_can_parse_schema_dump_for_a_basic_schema(): void
    {
        $schemaParser = new SquashedMigrationHelper(
            [__DIR__ . '/data/schema/basic_schema'],
            self::getContainer()->getByType(FileHelper::class),
            new MySqlDataTypeToPhpTypeConverter(),
            self::getContainer()->getService('sqlParser'),
            false,
        );

        $tables = $schemaParser->initializeTables();

        $this->assertCount(1, $tables);
        $this->assertArrayHasKey('accounts', $tables);
        $this->assertCount(8, $tables['accounts']->columns);
        $this->assertSame(['id', 'name', 'active', 'description', 'notes', 'profile_text', 'created_at', 'updated_at'], array_keys($tables['accounts']->columns));
        $this->assertSame('non-negative-int', $tables['accounts']->columns['id']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['name']->readableType);
        $this->assertSame(false, $tables['accounts']->columns['name']->nullable);
        $this->assertSame('string', $tables['accounts']->columns['active']->readableType);
        $this->assertSame(false, $tables['accounts']->columns['active']->nullable);
        $this->assertSame('string', $tables['accounts']->columns['description']->readableType);
        $this->assertSame(true, $tables['accounts']->columns['description']->nullable);
        $this->assertSame('string', $tables['accounts']->columns['notes']->readableType);
        $this->assertSame(true, $tables['accounts']->columns['notes']->nullable);
        $this->assertSame('string', $tables['accounts']->columns['profile_text']->readableType);
        $this->assertSame(false, $tables['accounts']->columns['profile_text']->nullable);
        $this->assertSame('string', $tables['accounts']->columns['created_at']->readableType);
        $this->assertSame(true, $tables['accounts']->columns['created_at']->nullable);
        $this->assertSame('string', $tables['accounts']->columns['updated_at']->readableType);
        $this->assertSame(true, $tables['accounts']->columns['updated_at']->nullable);
    }

    #[Test]
    public function it_will_ignore_if_table_already_exists_in_parsed_tables_array(): void
    {
        $schemaParser = new SquashedMigrationHelper(
            [__DIR__ . '/data/schema/multiple_schemas_for_same_table'],
            self::getContainer()->getByType(FileHelper::class),
            new MySqlDataTypeToPhpTypeConverter(),
            self::getContainer()->getService('sqlParser'),
            false,
        );

        $tables = $schemaParser->initializeTables();

        $this->assertCount(1, $tables);
        $this->assertArrayHasKey('accounts', $tables);
        $this->assertCount(6, $tables['accounts']->columns);
        $this->assertSame(['id', 'name', 'active', 'description', 'created_at', 'updated_at'], array_keys($tables['accounts']->columns));
        $this->assertSame('non-negative-int', $tables['accounts']->columns['id']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['name']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['active']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['description']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['created_at']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['updated_at']->readableType);
    }

    #[Test]
    public function it_can_find_schemas_with_sql_suffix(): void
    {
        $schemaParser = new SquashedMigrationHelper(
            [__DIR__ . '/data/schema/basic_schema_with_sql_extension'],
            self::getContainer()->getByType(FileHelper::class),
            new MySqlDataTypeToPhpTypeConverter(),
            self::getContainer()->getService('sqlParser'),
            false,
        );

        $tables = $schemaParser->initializeTables();

        $this->assertCount(1, $tables);
        $this->assertArrayHasKey('accounts', $tables);
        $this->assertCount(6, $tables['accounts']->columns);
        $this->assertSame(['id', 'name', 'active', 'description', 'created_at', 'updated_at'], array_keys($tables['accounts']->columns));
        $this->assertSame('non-negative-int', $tables['accounts']->columns['id']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['name']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['active']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['description']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['created_at']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['updated_at']->readableType);
    }

    #[Test]
    public function it_can_find_schemas_with_different_extensions(): void
    {
        $schemaParser = new SquashedMigrationHelper(
            [__DIR__ . '/data/schema/multiple_schemas_with_different_extensions'],
            self::getContainer()->getByType(FileHelper::class),
            new MySqlDataTypeToPhpTypeConverter(),
            self::getContainer()->getService('sqlParser'),
            false,
        );

        $tables = $schemaParser->initializeTables();

        $this->assertCount(2, $tables);
        $this->assertArrayHasKey('accounts', $tables);
        $this->assertCount(6, $tables['accounts']->columns);
        $this->assertSame(['id', 'name', 'active', 'description', 'created_at', 'updated_at'], array_keys($tables['accounts']->columns));
        $this->assertSame('non-negative-int', $tables['accounts']->columns['id']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['name']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['active']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['description']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['created_at']->readableType);
        $this->assertSame('string', $tables['accounts']->columns['updated_at']->readableType);
        $this->assertArrayHasKey('users', $tables);
        $this->assertCount(6, $tables['users']->columns);
        $this->assertSame(['id', 'name', 'active', 'description', 'created_at', 'updated_at'], array_keys($tables['users']->columns));
        $this->assertSame('non-negative-int', $tables['users']->columns['id']->readableType);
        $this->assertSame('string', $tables['users']->columns['name']->readableType);
        $this->assertSame('string', $tables['users']->columns['active']->readableType);
        $this->assertSame('string', $tables['users']->columns['description']->readableType);
        $this->assertSame('string', $tables['users']->columns['created_at']->readableType);
        $this->assertSame('string', $tables['users']->columns['updated_at']->readableType);
    }

    #[Test]
    public function it_can_disable_schema_scanning(): void
    {
        $schemaParser = new SquashedMigrationHelper(
            [__DIR__ . '/data/schema/multiple_schemas_with_different_extensions'],
            self::getContainer()->getByType(FileHelper::class),
            new MySqlDataTypeToPhpTypeConverter(),
            self::getContainer()->getService('sqlParser'),
            true,
        );

        $tables = $schemaParser->initializeTables();

        $this->assertSame([], $tables);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../phpstan-tests.neon'];
    }
}
