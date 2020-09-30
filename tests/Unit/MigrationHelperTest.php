<?php

declare(strict_types=1);

namespace Tests\Unit;

use NunoMaduro\Larastan\Properties\MigrationHelper;
use NunoMaduro\Larastan\Properties\SchemaTable;
use PHPStan\Parser\CachedParser;
use PHPStan\Testing\TestCase;

class MigrationHelperTest extends TestCase
{
    /** @var CachedParser */
    private $cachedParser;

    public function setUp(): void
    {
        $this->cachedParser = self::getContainer()->getByType(CachedParser::class);
    }

    /** @test */
    public function it_will_return_empty_array_if_migrations_path_is_not_a_directory()
    {
        $migrationHelper = new MigrationHelper($this->cachedParser, '', 'foobar');

        self::assertSame([], $migrationHelper->initializeTables());
    }

    /** @test */
    public function it_can_read_basic_migrations_and_create_table_structure()
    {
        $migrationHelper = new MigrationHelper($this->cachedParser, '', __DIR__.'/data/basic_migration');

        $tables = $migrationHelper->initializeTables();

        $this->assertUsersTableSchema($tables);
    }

    /** @test */
    public function it_can_read_schema_definitions_from_any_method_in_class()
    {
        $migrationHelper = new MigrationHelper($this->cachedParser, '', __DIR__.'/data/migrations_with_different_methods');

        $tables = $migrationHelper->initializeTables();

        $this->assertUsersTableSchema($tables);
    }

    /** @test */
    public function it_can_read_schema_definitions_with_multiple_create_and_drop_methods_for_one_table()
    {
        $migrationHelper = new MigrationHelper($this->cachedParser, '', __DIR__.'/data/complex_migrations');

        $tables = $migrationHelper->initializeTables();

        self::assertCount(1, $tables);
        self::assertArrayHasKey('users', $tables);
        self::assertCount(6, $tables['users']->columns);
        self::assertSame(['id', 'email', 'birthday', 'created_at', 'updated_at', 'active'], array_keys($tables['users']->columns));
        self::assertSame('int', $tables['users']->columns['id']->readableType);
        self::assertSame('string', $tables['users']->columns['email']->readableType);
        self::assertSame('string', $tables['users']->columns['birthday']->readableType);
        self::assertSame('string', $tables['users']->columns['created_at']->readableType);
        self::assertSame('string', $tables['users']->columns['updated_at']->readableType);
        self::assertSame('int', $tables['users']->columns['active']->readableType);
    }

    /**
     * @param array<string, SchemaTable> $tables
     */
    private function assertUsersTableSchema(array $tables): void
    {
        self::assertCount(1, $tables);
        self::assertArrayHasKey('users', $tables);
        self::assertCount(5, $tables['users']->columns);
        self::assertSame(['id', 'name', 'email', 'created_at', 'updated_at'], array_keys($tables['users']->columns));
        self::assertSame('int', $tables['users']->columns['id']->readableType);
        self::assertSame('string', $tables['users']->columns['name']->readableType);
        self::assertSame('string', $tables['users']->columns['email']->readableType);
        self::assertSame('string', $tables['users']->columns['created_at']->readableType);
        self::assertSame('string', $tables['users']->columns['updated_at']->readableType);
    }
}
