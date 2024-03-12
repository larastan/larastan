<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPStan\Testing\PHPStanTestCase;
use Tests\Unit\Concerns\HasDatabaseHelper;

use function array_keys;

/** @covers \Larastan\Larastan\Properties\SquashedMigrationHelper */
class SquashedMigrationHelperTest extends PHPStanTestCase
{
    use HasDatabaseHelper;

    /** @test */
    public function it_can_parse_basic_schema_in_different_formats(): void
    {
        $this->getSquashedMigrationHelper([__DIR__ . '/data/schema/basic_schema'])
            ->parseSchemaDumps($this->modelDatabaseHelper);

        $this->assertCount(2, $this->modelDatabaseHelper->connections);
        $this->assertArrayHasKey('default', $this->modelDatabaseHelper->connections);
        $this->assertArrayHasKey('nondefault', $this->modelDatabaseHelper->connections);

        foreach ($this->modelDatabaseHelper->connections as $connection) {
            $tables = $connection->tables;

            $this->assertCount(6, $tables['accounts']->columns);
            $this->assertSame(['id', 'name', 'active', 'description', 'created_at', 'updated_at'], array_keys($tables['accounts']->columns));
            $this->assertSame('int', $tables['accounts']->columns['id']->readableType);
            $this->assertSame('string', $tables['accounts']->columns['name']->readableType);
            $this->assertSame('string', $tables['accounts']->columns['active']->readableType);
            $this->assertSame('string', $tables['accounts']->columns['description']->readableType);
            $this->assertSame('string', $tables['accounts']->columns['created_at']->readableType);
            $this->assertSame('string', $tables['accounts']->columns['updated_at']->readableType);
        }
    }

    /** @test */
    public function it_will_ignore_if_table_already_exists_in_parsed_tables_array(): void
    {
        $this->getSquashedMigrationHelper([__DIR__ . '/data/schema/schema_with_create_statements_for_same_table'])
            ->parseSchemaDumps($this->modelDatabaseHelper);

        $this->assertCount(1, $this->modelDatabaseHelper->connections);
        $this->assertArrayHasKey('mysql', $this->modelDatabaseHelper->connections);
        $tables = $this->modelDatabaseHelper->connections['mysql']->tables;
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
        $this->getSquashedMigrationHelper([__DIR__ . '/data/schema/schema_with_nonstandard_name'])
            ->parseSchemaDumps($this->modelDatabaseHelper);

        $this->assertCount(1, $this->modelDatabaseHelper->connections);
        $this->assertArrayHasKey('pgsql', $this->modelDatabaseHelper->connections);
        $tables = $this->modelDatabaseHelper->connections['pgsql']->tables;
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
        $this->getSquashedMigrationHelper([__DIR__ . '/data/schema/basic_schema'], true)
            ->parseSchemaDumps($this->modelDatabaseHelper);

        $this->assertSame([], $this->modelDatabaseHelper->connections);
    }
}
