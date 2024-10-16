<?php

declare(strict_types=1);

namespace Tests\Unit;

use Larastan\Larastan\Internal\FileHelper;
use Larastan\Larastan\Properties\MigrationHelper;
use Larastan\Larastan\Properties\SchemaTable;
use PHPStan\File\FileHelper as PHPStanFileHelper;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;

use function array_keys;

class MigrationHelperTest extends PHPStanTestCase
{
    private Parser $parser;

    private FileHelper $fileHelper;

    private ReflectionProvider $reflectionProvider;

    public function setUp(): void
    {
        $this->parser             = self::getContainer()->getService('currentPhpVersionSimpleDirectParser');
        $this->fileHelper         = new FileHelper(
            self::getContainer()->getByType(PHPStanFileHelper::class),
        );
        $this->reflectionProvider = $this->createReflectionProvider();
    }

    /** @test */
    public function it_will_return_empty_array_if_migrations_path_is_not_a_directory(): void
    {
        $migrationHelper = new MigrationHelper($this->parser, ['foobar'], $this->fileHelper, false, $this->reflectionProvider);

        self::assertSame([], $migrationHelper->initializeTables());
    }

    /** @test */
    public function it_can_read_basic_migrations_and_create_table_structure(): void
    {
        $migrationHelper = new MigrationHelper($this->parser, [__DIR__ . '/data/basic_migration'], $this->fileHelper, false, $this->reflectionProvider);

        $tables = $migrationHelper->initializeTables();

        $this->assertUsersTableSchema($tables);
    }

    /** @test */
    public function it_can_read_schema_definitions_from_any_method_in_class(): void
    {
        $migrationHelper = new MigrationHelper($this->parser, [__DIR__ . '/data/migrations_with_different_methods'], $this->fileHelper, false, $this->reflectionProvider);

        $tables = $migrationHelper->initializeTables();

        $this->assertUsersTableSchema($tables);
    }

    /** @test */
    public function it_can_read_schema_definitions_with_multiple_create_and_drop_methods_for_one_table(): void
    {
        $migrationHelper = new MigrationHelper($this->parser, [__DIR__ . '/data/complex_migrations'], $this->fileHelper, false, $this->reflectionProvider);

        $tables = $migrationHelper->initializeTables();

        self::assertCount(1, $tables);
        self::assertArrayHasKey('users', $tables);
        self::assertCount(8, $tables['users']->columns);
        self::assertSame(['id', 'email', 'birthday', 'city', 'zip', 'created_at', 'updated_at', 'active'], array_keys($tables['users']->columns));
        self::assertSame('int', $tables['users']->columns['id']->readableType);
        self::assertSame('string', $tables['users']->columns['email']->readableType);
        self::assertSame('string', $tables['users']->columns['birthday']->readableType);
        self::assertSame('string', $tables['users']->columns['city']->readableType);
        self::assertSame(true, $tables['users']->columns['city']->nullable);
        self::assertSame('int', $tables['users']->columns['zip']->readableType);
        self::assertSame(false, $tables['users']->columns['zip']->nullable);
        self::assertSame('string', $tables['users']->columns['created_at']->readableType);
        self::assertSame('string', $tables['users']->columns['updated_at']->readableType);
        self::assertSame('int', $tables['users']->columns['active']->readableType);
    }

    /** @test */
    public function it_can_read_additional_directories(): void
    {
        $migrationHelper = new MigrationHelper($this->parser, [
            __DIR__ . '/data/basic_migration',
            __DIR__ . '/data/additional_migrations',
        ], $this->fileHelper, false, $this->reflectionProvider);

        $tables = $migrationHelper->initializeTables();

        self::assertCount(2, $tables);
        self::assertArrayHasKey('users', $tables);
        self::assertArrayHasKey('teams', $tables);
    }

    /** @test */
    public function it_can_handle_use_of_after_method_in_migration(): void
    {
        $migrationHelper = new MigrationHelper($this->parser, [
            __DIR__ . '/data/migrations_using_after_method',
        ], $this->fileHelper, false, $this->reflectionProvider);

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
    public function it_can_handle_alter_table_and_column_rename(): void
    {
        $migrationHelper = new MigrationHelper($this->parser, [
            __DIR__ . '/data/rename_migrations',
        ], $this->fileHelper, false, $this->reflectionProvider);

        $tables = $migrationHelper->initializeTables();

        self::assertCount(1, $tables);
        self::assertArrayNotHasKey('users', $tables);
        self::assertArrayHasKey('accounts', $tables);
        $columns = $tables['accounts']->columns;
        self::assertArrayNotHasKey('name', $columns);
        self::assertArrayHasKey('full_name', $columns);
        self::assertSame('string', $columns['full_name']->readableType);
    }

    /** @test */
    public function it_can_handle_migrations_with_soft_deletes(): void
    {
        $migrationHelper = new MigrationHelper($this->parser, [__DIR__ . '/data/migrations_using_soft_deletes'], $this->fileHelper, false, $this->reflectionProvider);

        $tables = $migrationHelper->initializeTables();

        self::assertCount(1, $tables);
        self::assertArrayHasKey('users', $tables);
        self::assertCount(6, $tables['users']->columns);
        self::assertSame('string', $tables['users']->columns['deleted_at']->readableType);
    }

    /** @test */
    public function it_can_handle_migrations_with_soft_deletes_tz(): void
    {
        $migrationHelper = new MigrationHelper($this->parser, [__DIR__ . '/data/migrations_using_soft_deletes_tz'], $this->fileHelper, false, $this->reflectionProvider);

        $tables = $migrationHelper->initializeTables();

        self::assertCount(1, $tables);
        self::assertArrayHasKey('users', $tables);
        self::assertCount(6, $tables['users']->columns);
        self::assertSame('string', $tables['users']->columns['deleted_at']->readableType);
    }

    /** @test */
    public function it_can_handle_migrations_with_default_arguments(): void
    {
        $migrationHelper = new MigrationHelper($this->parser, [__DIR__ . '/data/migration_with_default_arguments'], $this->fileHelper, false, $this->reflectionProvider);

        $tables = $migrationHelper->initializeTables();

        self::assertCount(1, $tables);
        self::assertArrayHasKey('users', $tables);
        self::assertCount(11, $tables['users']->columns);
        self::assertSame('int', $tables['users']->columns['id']->readableType);
        self::assertSame('string', $tables['users']->columns['ip_address']->readableType);
        self::assertSame('string', $tables['users']->columns['custom_ip_address']->readableType);
        self::assertSame('string', $tables['users']->columns['mac_address']->readableType);
        self::assertSame('string', $tables['users']->columns['custom_mac_address']->readableType);
        self::assertSame('string', $tables['users']->columns['uuid']->readableType);
        self::assertSame('string', $tables['users']->columns['custom_uuid']->readableType);
        self::assertSame('string', $tables['users']->columns['ulid']->readableType);
        self::assertSame('string', $tables['users']->columns['custom_ulid']->readableType);
        self::assertSame('string', $tables['users']->columns['deleted_at']->readableType);
        self::assertSame('string', $tables['users']->columns['custom_soft_deletes']->readableType);
    }

    /** @test */
    public function it_can_handle_connection_before_schema_create(): void
    {
        $migrationHelper = new MigrationHelper($this->parser, [__DIR__ . '/data/migration_with_schema_connection'], $this->fileHelper, false, $this->reflectionProvider);

        $tables = $migrationHelper->initializeTables();

        $this->assertUsersTableSchema($tables);
    }

    /** @test */
    public function it_can_disable_migration_scanning(): void
    {
        $migrationHelper = new MigrationHelper($this->parser, [
            __DIR__ . '/data/basic_migration',
            __DIR__ . '/data/additional_migrations',
        ], $this->fileHelper, true, $this->reflectionProvider);

        $tables = $migrationHelper->initializeTables();

        self::assertSame([], $tables);
    }

    /** @test */
    public function it_can_handle_nullable_in_migrations(): void
    {
        $migrationHelper = new MigrationHelper($this->parser, [__DIR__ . '/data/migrations_using_nullable'], $this->fileHelper, false, $this->reflectionProvider);

        $tables = $migrationHelper->initializeTables();

        self::assertSame(false, $tables['users']->columns['name']->nullable);
        self::assertSame(true, $tables['users']->columns['email']->nullable);
        self::assertSame(true, $tables['users']->columns['address1']->nullable);
    }

    /** @param  array<string, SchemaTable> $tables */
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

    /** @test */
    public function it_can_handle_migrations_with_array_passed_to_drop_column(): void
    {
        $migrationHelper = new MigrationHelper($this->parser, [
            __DIR__ . '/data/migrations_using_drop_column',
        ], $this->fileHelper, false, $this->reflectionProvider);

        $tables = $migrationHelper->initializeTables();

        self::assertCount(1, $tables);
        self::assertArrayHasKey('users', $tables);
        self::assertCount(5, $tables['users']->columns);
        self::assertSame(['id', 'name', 'email', 'created_at', 'updated_at'], array_keys($tables['users']->columns));
    }

    /** @test */
    public function it_can_handle_migrations_with_if_statements(): void
    {
        $migrationHelper = new MigrationHelper($this->parser, [__DIR__ . '/data/conditional_migrations'], $this->fileHelper, false, $this->reflectionProvider);

        $tables = $migrationHelper->initializeTables();

        self::assertArrayHasKey('id', $tables['users']->columns);
        self::assertArrayHasKey('name', $tables['users']->columns);
        self::assertArrayHasKey('email', $tables['users']->columns);
        self::assertArrayHasKey('address1', $tables['users']->columns);
        self::assertArrayHasKey('address2', $tables['users']->columns);
    }
}
