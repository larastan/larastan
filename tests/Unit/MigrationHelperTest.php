<?php

declare(strict_types=1);

namespace Tests\Unit;

use Larastan\Larastan\Properties\SchemaTable;
use PHPStan\Testing\PHPStanTestCase;
use Tests\Unit\Concerns\HasDatabaseHelper;

use function array_keys;

class MigrationHelperTest extends PHPStanTestCase
{
    use HasDatabaseHelper;

    /** @test */
    public function it_will_return_empty_array_if_migrations_path_is_not_a_directory(): void
    {
        $this->getMigrationHelper(['foobar'])
            ->initializeTables($this->modelDatabaseHelper);

        self::assertSame([], $this->modelDatabaseHelper->connections);
    }

    /** @test */
    public function it_can_read_basic_migrations_and_create_table_structure(): void
    {
        $this->getMigrationHelper([__DIR__ . '/data/basic_migration'])
            ->initializeTables($this->modelDatabaseHelper);

        self::assertCount(1, $this->modelDatabaseHelper->connections);
        self::assertArrayHasKey($this->defaultConnection, $this->modelDatabaseHelper->connections);

        $tables = $this->modelDatabaseHelper->connections[$this->defaultConnection]->tables;

        $this->assertUsersTableSchema($tables);
    }

    /** @test */
    public function it_can_read_schema_definitions_from_any_method_in_class(): void
    {
        $this->getMigrationHelper([__DIR__ . '/data/migrations_with_different_methods'])
            ->initializeTables($this->modelDatabaseHelper);

        self::assertCount(1, $this->modelDatabaseHelper->connections);
        self::assertArrayHasKey($this->defaultConnection, $this->modelDatabaseHelper->connections);

        $tables = $this->modelDatabaseHelper->connections[$this->defaultConnection]->tables;

        $this->assertUsersTableSchema($tables);
    }

    /** @test */
    public function it_can_read_schema_definitions_with_multiple_create_and_drop_methods_for_one_table(): void
    {
        $this->getMigrationHelper([__DIR__ . '/data/complex_migrations'])
            ->initializeTables($this->modelDatabaseHelper);

        self::assertCount(1, $this->modelDatabaseHelper->connections);
        self::assertArrayHasKey($this->defaultConnection, $this->modelDatabaseHelper->connections);

        $tables = $this->modelDatabaseHelper->connections[$this->defaultConnection]->tables;

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
        $this->getMigrationHelper([
            __DIR__ . '/data/basic_migration',
            __DIR__ . '/data/additional_migrations',
        ])->initializeTables($this->modelDatabaseHelper);

        self::assertCount(1, $this->modelDatabaseHelper->connections);
        self::assertArrayHasKey($this->defaultConnection, $this->modelDatabaseHelper->connections);

        $tables = $this->modelDatabaseHelper->connections[$this->defaultConnection]->tables;

        self::assertCount(2, $tables);
        self::assertArrayHasKey('users', $tables);
        self::assertArrayHasKey('teams', $tables);
    }

    /** @test */
    public function it_can_handle_use_of_after_method_in_migration(): void
    {
        $this->getMigrationHelper([__DIR__ . '/data/migrations_using_after_method'])
            ->initializeTables($this->modelDatabaseHelper);

        self::assertCount(1, $this->modelDatabaseHelper->connections);
        self::assertArrayHasKey($this->defaultConnection, $this->modelDatabaseHelper->connections);

        $tables = $this->modelDatabaseHelper->connections[$this->defaultConnection]->tables;

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
        $this->getMigrationHelper([__DIR__ . '/data/rename_migrations'])
            ->initializeTables($this->modelDatabaseHelper);

        self::assertCount(1, $this->modelDatabaseHelper->connections);
        self::assertArrayHasKey($this->defaultConnection, $this->modelDatabaseHelper->connections);

        $tables = $this->modelDatabaseHelper->connections[$this->defaultConnection]->tables;

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
        $this->getMigrationHelper([__DIR__ . '/data/migrations_using_soft_deletes'])
            ->initializeTables($this->modelDatabaseHelper);

        self::assertCount(1, $this->modelDatabaseHelper->connections);
        self::assertArrayHasKey($this->defaultConnection, $this->modelDatabaseHelper->connections);

        $tables = $this->modelDatabaseHelper->connections[$this->defaultConnection]->tables;

        self::assertCount(1, $tables);
        self::assertArrayHasKey('users', $tables);
        self::assertCount(6, $tables['users']->columns);
        self::assertSame('string', $tables['users']->columns['deleted_at']->readableType);
    }

    /** @test */
    public function it_can_handle_migrations_with_soft_deletes_tz(): void
    {
        $this->getMigrationHelper([__DIR__ . '/data/migrations_using_soft_deletes_tz'])
            ->initializeTables($this->modelDatabaseHelper);

        self::assertCount(1, $this->modelDatabaseHelper->connections);
        self::assertArrayHasKey($this->defaultConnection, $this->modelDatabaseHelper->connections);

        $tables = $this->modelDatabaseHelper->connections[$this->defaultConnection]->tables;

        self::assertCount(1, $tables);
        self::assertArrayHasKey('users', $tables);
        self::assertCount(6, $tables['users']->columns);
        self::assertSame('string', $tables['users']->columns['deleted_at']->readableType);
    }

    /** @test */
    public function it_can_handle_migrations_with_default_arguments(): void
    {
        $this->getMigrationHelper([__DIR__ . '/data/migration_with_default_arguments'])
            ->initializeTables($this->modelDatabaseHelper);

        self::assertCount(1, $this->modelDatabaseHelper->connections);
        self::assertArrayHasKey($this->defaultConnection, $this->modelDatabaseHelper->connections);

        $tables = $this->modelDatabaseHelper->connections[$this->defaultConnection]->tables;

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
    public function it_can_handle_different_schema_connections(): void
    {
        $this->getMigrationHelper([__DIR__ . '/data/migration_with_schema_connection'])
            ->initializeTables($this->modelDatabaseHelper);

        self::assertCount(3, $this->modelDatabaseHelper->connections);
        self::assertArrayHasKey('foo', $this->modelDatabaseHelper->connections);
        self::assertArrayHasKey('bar', $this->modelDatabaseHelper->connections);
        self::assertArrayHasKey('baz', $this->modelDatabaseHelper->connections);

        $foo = $this->modelDatabaseHelper->connections['foo']->tables;
        $bar = $this->modelDatabaseHelper->connections['bar']->tables;
        $baz = $this->modelDatabaseHelper->connections['baz']->tables;

        self::assertCount(2, $foo);
        self::assertArrayHasKey('teams', $foo);
        self::assertArrayHasKey('users', $foo);

        self::assertCount(5, $foo['teams']->columns);
        self::assertSame(['id', 'team', 'owner_email', 'created_at', 'updated_at'], array_keys($foo['teams']->columns));
        self::assertSame('int', $foo['teams']->columns['id']->readableType);
        self::assertSame('string', $foo['teams']->columns['team']->readableType);
        self::assertSame('string', $foo['teams']->columns['owner_email']->readableType);
        self::assertSame('string', $foo['teams']->columns['created_at']->readableType);
        self::assertSame('string', $foo['teams']->columns['updated_at']->readableType);

        self::assertCount(1, $foo['users']->columns);
        self::assertSame(['id'], array_keys($foo['users']->columns));
        self::assertSame('int', $foo['users']->columns['id']->readableType);

        self::assertCount(2, $bar);
        self::assertArrayHasKey('users', $bar);
        self::assertArrayHasKey('teams', $bar);

        self::assertCount(5, $bar['users']->columns);
        self::assertSame(['id', 'name', 'email', 'created_at', 'updated_at'], array_keys($bar['users']->columns));
        self::assertSame('int', $bar['users']->columns['id']->readableType);
        self::assertSame('string', $bar['users']->columns['name']->readableType);
        self::assertSame('string', $bar['users']->columns['email']->readableType);
        self::assertSame('string', $bar['users']->columns['created_at']->readableType);
        self::assertSame('string', $bar['users']->columns['updated_at']->readableType);

        self::assertCount(1, $bar['teams']->columns);
        self::assertSame(['id'], array_keys($bar['teams']->columns));
        self::assertSame('int', $bar['teams']->columns['id']->readableType);

        $this->assertUsersTableSchema($baz);
    }

    /** @test */
    public function it_can_handle_nullable_in_migrations(): void
    {
        $this->getMigrationHelper([__DIR__ . '/data/migrations_using_nullable'])
            ->initializeTables($this->modelDatabaseHelper);

        self::assertCount(1, $this->modelDatabaseHelper->connections);
        self::assertArrayHasKey($this->defaultConnection, $this->modelDatabaseHelper->connections);

        $tables = $this->modelDatabaseHelper->connections[$this->defaultConnection]->tables;

        self::assertSame(false, $tables['users']->columns['name']->nullable);
        self::assertSame(true, $tables['users']->columns['email']->nullable);
        self::assertSame(true, $tables['users']->columns['address1']->nullable);
    }

    /** @test */
    public function it_can_handle_migrations_with_array_passed_to_drop_column(): void
    {
        $this->getMigrationHelper([__DIR__ . '/data/migrations_using_drop_column'])
            ->initializeTables($this->modelDatabaseHelper);

        self::assertCount(1, $this->modelDatabaseHelper->connections);
        self::assertArrayHasKey($this->defaultConnection, $this->modelDatabaseHelper->connections);

        $tables = $this->modelDatabaseHelper->connections[$this->defaultConnection]->tables;

        self::assertCount(1, $tables);
        self::assertArrayHasKey('users', $tables);
        self::assertCount(5, $tables['users']->columns);
        self::assertSame(['id', 'name', 'email', 'created_at', 'updated_at'], array_keys($tables['users']->columns));
    }

    /** @test */
    public function it_can_handle_migrations_with_if_statements(): void
    {
        $this->getMigrationHelper([__DIR__ . '/data/conditional_migrations'])
            ->initializeTables($this->modelDatabaseHelper);

        self::assertCount(1, $this->modelDatabaseHelper->connections);
        self::assertArrayHasKey($this->defaultConnection, $this->modelDatabaseHelper->connections);

        $tables = $this->modelDatabaseHelper->connections[$this->defaultConnection]->tables;

        self::assertArrayHasKey('id', $tables['users']->columns);
        self::assertArrayHasKey('name', $tables['users']->columns);
        self::assertArrayHasKey('email', $tables['users']->columns);
        self::assertArrayHasKey('address1', $tables['users']->columns);
        self::assertArrayHasKey('address2', $tables['users']->columns);
    }

    /** @test */
    public function it_can_disable_migration_scanning(): void
    {
        $this->getMigrationHelper([__DIR__ . '/data/basic_migration'], true)
            ->initializeTables($this->modelDatabaseHelper);

        self::assertSame([], $this->modelDatabaseHelper->connections);
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
}
