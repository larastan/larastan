<?php

declare(strict_types=1);

namespace Tests\Unit;

use Larastan\Larastan\Properties\MigrationHelper;
use Larastan\Larastan\Properties\SchemaTable;
use Larastan\Larastan\Support\ModelHelper;
use PHPStan\File\FileHelper;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\Test;

use function array_keys;

class MigrationHelperTest extends PHPStanTestCase
{
    private Parser $parser;

    private FileHelper $fileHelper;

    private ModelHelper $modelHelper;

    private ReflectionProvider $reflectionProvider;

    public function setUp(): void
    {
        $this->parser             = self::getContainer()->getService('currentPhpVersionSimpleDirectParser');
        $this->fileHelper         = self::getContainer()->getByType(FileHelper::class);
        $this->reflectionProvider = self::createReflectionProvider();
        $this->modelHelper        = new ModelHelper($this->reflectionProvider);
    }

    #[Test]
    public function it_will_return_empty_array_if_migrations_path_is_not_a_directory(): void
    {
        $tables = $this->getMigrationHelper(['foobar'])->initializeTables();

        self::assertSame([], $tables);
    }

    #[Test]
    public function it_can_read_basic_migrations_and_create_table_structure(): void
    {
        $tables = $this->getMigrationHelper([__DIR__ . '/data/basic_migration'])->initializeTables();

        $this->assertUsersTableSchema($tables);
    }

    #[Test]
    public function it_can_read_schema_definitions_from_any_method_in_class(): void
    {
        $tables = $this->getMigrationHelper([__DIR__ . '/data/migrations_with_different_methods'])->initializeTables();

        $this->assertUsersTableSchema($tables);
    }

    #[Test]
    public function it_can_read_schema_definitions_with_multiple_create_and_drop_methods_for_one_table(): void
    {
        $tables = $this->getMigrationHelper([__DIR__ . '/data/complex_migrations'])->initializeTables();

        self::assertCount(1, $tables);
        self::assertArrayHasKey('users', $tables);
        self::assertCount(8, $tables['users']->columns);
        self::assertSame(['id', 'email', 'birthday', 'city', 'zip', 'created_at', 'updated_at', 'active'], array_keys($tables['users']->columns));
        self::assertSame('non-negative-int', $tables['users']->columns['id']->readableType);
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

    #[Test]
    public function it_can_read_additional_directories(): void
    {
        $tables = $this->getMigrationHelper([__DIR__ . '/data/basic_migration', __DIR__ . '/data/additional_migrations'])->initializeTables();

        self::assertCount(2, $tables);
        self::assertArrayHasKey('users', $tables);
        self::assertArrayHasKey('teams', $tables);
    }

    #[Test]
    public function it_can_handle_use_of_after_method_in_migration(): void
    {
        $tables = $this->getMigrationHelper([__DIR__ . '/data/migrations_using_after_method'])->initializeTables();

        self::assertCount(1, $tables);
        self::assertArrayHasKey('users', $tables);
        self::assertCount(5, $tables['users']->columns);
        self::assertSame(['id', 'name', 'created_at', 'updated_at', 'email'], array_keys($tables['users']->columns));
        self::assertSame('non-negative-int', $tables['users']->columns['id']->readableType);
        self::assertSame('string', $tables['users']->columns['name']->readableType);
        self::assertSame('string', $tables['users']->columns['email']->readableType);
        self::assertSame('string', $tables['users']->columns['created_at']->readableType);
        self::assertSame('string', $tables['users']->columns['updated_at']->readableType);
    }

    #[Test]
    public function it_can_handle_alter_table_and_column_rename(): void
    {
        $tables = $this->getMigrationHelper([__DIR__ . '/data/rename_migrations'])->initializeTables();

        self::assertCount(1, $tables);
        self::assertArrayNotHasKey('users', $tables);
        self::assertArrayHasKey('accounts', $tables);
        $columns = $tables['accounts']->columns;
        self::assertArrayNotHasKey('name', $columns);
        self::assertArrayHasKey('full_name', $columns);
        self::assertSame('string', $columns['full_name']->readableType);
    }

    #[Test]
    public function it_can_handle_migrations_with_soft_deletes(): void
    {
        $tables = $this->getMigrationHelper([__DIR__ . '/data/migrations_using_soft_deletes'])->initializeTables();

        self::assertCount(1, $tables);
        self::assertArrayHasKey('users', $tables);
        self::assertCount(6, $tables['users']->columns);
        self::assertSame('string', $tables['users']->columns['deleted_at']->readableType);
    }

    #[Test]
    public function it_can_handle_migrations_with_soft_deletes_tz(): void
    {
        $tables = $this->getMigrationHelper([__DIR__ . '/data/migrations_using_soft_deletes_tz'])->initializeTables();

        self::assertCount(1, $tables);
        self::assertArrayHasKey('users', $tables);
        self::assertCount(6, $tables['users']->columns);
        self::assertSame('string', $tables['users']->columns['deleted_at']->readableType);
    }

    #[Test]
    public function it_can_handle_migrations_with_default_arguments(): void
    {
        $tables = $this->getMigrationHelper([__DIR__ . '/data/migration_with_default_arguments'])->initializeTables();

        self::assertCount(1, $tables);
        self::assertArrayHasKey('users', $tables);
        self::assertCount(11, $tables['users']->columns);
        self::assertSame('non-negative-int', $tables['users']->columns['id']->readableType);
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

    #[Test]
    public function it_can_handle_connection_before_schema_create(): void
    {
        $tables = $this->getMigrationHelper([__DIR__ . '/data/migration_with_schema_connection'])->initializeTables();

        $this->assertUsersTableSchema($tables);
    }

    #[Test]
    public function it_can_disable_migration_scanning(): void
    {
        $tables = $this->getMigrationHelper([__DIR__ . '/data/basic_migration', __DIR__ . '/data/additional_migrations'], true)->initializeTables();

        self::assertSame([], $tables);
    }

    #[Test]
    public function it_can_handle_nullable_in_migrations(): void
    {
        $tables = $this->getMigrationHelper([__DIR__ . '/data/migrations_using_nullable'])->initializeTables();

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
        self::assertSame('non-negative-int', $tables['users']->columns['id']->readableType);
        self::assertSame('string', $tables['users']->columns['name']->readableType);
        self::assertSame('string', $tables['users']->columns['email']->readableType);
        self::assertSame('string', $tables['users']->columns['created_at']->readableType);
        self::assertSame('string', $tables['users']->columns['updated_at']->readableType);
    }

    #[Test]
    public function it_can_handle_migrations_with_array_passed_to_drop_column(): void
    {
        $tables = $this->getMigrationHelper([__DIR__ . '/data/migrations_using_drop_column'])->initializeTables();

        self::assertCount(1, $tables);
        self::assertArrayHasKey('users', $tables);
        self::assertCount(5, $tables['users']->columns);
        self::assertSame(['id', 'name', 'email', 'created_at', 'updated_at'], array_keys($tables['users']->columns));
    }

    #[Test]
    public function it_can_handle_migrations_with_if_statements(): void
    {
        $tables = $this->getMigrationHelper([__DIR__ . '/data/conditional_migrations'])->initializeTables();

        self::assertArrayHasKey('id', $tables['users']->columns);
        self::assertArrayHasKey('name', $tables['users']->columns);
        self::assertArrayHasKey('email', $tables['users']->columns);
        self::assertArrayHasKey('address1', $tables['users']->columns);
        self::assertArrayHasKey('address2', $tables['users']->columns);
    }

    #[Test]
    public function it_can_handle_migrations_with_const_as_table(): void
    {
        $tables = $this->getMigrationHelper([__DIR__ . '/data/migration_with_const'])->initializeTables();

        self::assertArrayHasKey('id', $tables['users']->columns);
        self::assertArrayHasKey('name', $tables['users']->columns);
        self::assertArrayHasKey('email', $tables['users']->columns);
    }

    /** @param string[] $paths */
    private function getMigrationHelper(array $paths, bool $scan = false): MigrationHelper
    {
        return new MigrationHelper($this->parser, $paths, $this->fileHelper, $scan, $this->reflectionProvider, $this->modelHelper);
    }
}
