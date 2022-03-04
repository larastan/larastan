<?php

declare(strict_types=1);

namespace Tests\Unit;

use NunoMaduro\Larastan\Properties\MigrationHelper;
use NunoMaduro\Larastan\Properties\SchemaTable;
use PHPStan\File\FileHelper;
use PHPStan\Parser\Parser;
use PHPStan\Testing\PHPStanTestCase;

class MigrationHelperTest extends PHPStanTestCase
{
    /** @var Parser */
    private $parser;

    /** @var FileHelper */
    private $fileHelper;

    public function setUp(): void
    {
        $this->parser = self::getContainer()->getService('currentPhpVersionSimpleDirectParser');
        $this->fileHelper = self::getContainer()->getByType(FileHelper::class);
    }

    /** @test */
    public function it_will_return_empty_array_if_migrations_path_is_not_a_directory()
    {
        $migrationHelper = new MigrationHelper($this->parser, ['foobar'], $this->fileHelper);

        self::assertSame([], $migrationHelper->initializeTables());
    }

    /** @test */
    public function it_can_read_basic_migrations_and_create_table_structure()
    {
        $migrationHelper = new MigrationHelper($this->parser, [__DIR__.'/data/basic_migration'], $this->fileHelper);

        $tables = $migrationHelper->initializeTables();

        $this->assertUsersTableSchema($tables);
    }

    /** @test */
    public function it_can_read_schema_definitions_from_any_method_in_class()
    {
        $migrationHelper = new MigrationHelper($this->parser, [__DIR__.'/data/migrations_with_different_methods'], $this->fileHelper);

        $tables = $migrationHelper->initializeTables();

        $this->assertUsersTableSchema($tables);
    }

    /** @test */
    public function it_can_read_schema_definitions_with_multiple_create_and_drop_methods_for_one_table()
    {
        $migrationHelper = new MigrationHelper($this->parser, [__DIR__.'/data/complex_migrations'], $this->fileHelper);

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

    /** @test */
    public function it_can_read_additional_directories(): void
    {
        $migrationHelper = new MigrationHelper($this->parser, [
            __DIR__.'/data/basic_migration',
            __DIR__.'/data/additional_migrations',
        ], $this->fileHelper);

        $tables = $migrationHelper->initializeTables();

        self::assertCount(2, $tables);
        self::assertArrayHasKey('users', $tables);
        self::assertArrayHasKey('teams', $tables);
    }

    /** @test */
    public function it_can_handle_use_of_after_method_in_migration(): void
    {
        $migrationHelper = new MigrationHelper($this->parser, [
            __DIR__.'/data/migrations_using_after_method',
        ], $this->fileHelper);

        $tables = $migrationHelper->initializeTables();

        self::assertCount(1, $tables);
        self::assertArrayHasKey('users', $tables);
        self::assertCount(5, $tables['users']->columns);
        self::assertSame(['id', 'name', 'created_at', 'updated_at', 'email'], array_keys($tables['users']->columns));
        self::assertSame('int', $tables['users']->columns['id']->readableType);
        self::assertSame('string', $tables['users']->columns['name']->readableType);
        self::assertSame('string', $tables['users']->columns['email']->readableType);
        self::assertSame('string', $tables['users']->columns['created_at']->readableType);
        self::assertSame('string', $tables['users']->columns['updated_at']->readableType);
    }

    /** @test */
    public function it_can_handle_alter_table_rename()
    {
        $migrationHelper = new MigrationHelper($this->parser, [
            __DIR__.'/data/rename_migrations',
        ], $this->fileHelper);

        $tables = $migrationHelper->initializeTables();

        self::assertCount(1, $tables);
        self::assertArrayNotHasKey('users', $tables);
        self::assertArrayHasKey('accounts', $tables);
    }

    /** @test */
    public function it_can_handle_migrations_with_soft_deletes()
    {
        $migrationHelper = new MigrationHelper($this->parser, [__DIR__.'/data/migrations_using_soft_deletes'], $this->fileHelper);

        $tables = $migrationHelper->initializeTables();

        self::assertCount(1, $tables);
        self::assertArrayHasKey('users', $tables);
        self::assertCount(6, $tables['users']->columns);
        self::assertSame('string', $tables['users']->columns['deleted_at']->readableType);
    }

    /** @test */
    public function it_can_handle_migrations_with_soft_deletes_tz()
    {
        $migrationHelper = new MigrationHelper($this->parser, [__DIR__.'/data/migrations_using_soft_deletes_tz'], $this->fileHelper);

        $tables = $migrationHelper->initializeTables();

        self::assertCount(1, $tables);
        self::assertArrayHasKey('users', $tables);
        self::assertCount(6, $tables['users']->columns);
        self::assertSame('string', $tables['users']->columns['deleted_at']->readableType);
    }

    /** @test */
    public function it_can_handle_connection_before_schema_create()
    {
        $migrationHelper = new MigrationHelper($this->parser, [__DIR__.'/data/migration_with_schema_connection'], $this->fileHelper);

        $tables = $migrationHelper->initializeTables();

        $this->assertUsersTableSchema($tables);
    }

    /**
     * @param  array<string, SchemaTable>  $tables
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
