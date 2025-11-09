<?php

declare(strict_types=1);

namespace Tests\Unit\SQL;

use Larastan\Larastan\SQL\IamcalSqlParser;
use Larastan\Larastan\SQL\SqlParserFailure;
use PHPUnit\Framework\TestCase;

final class IamcalSqlParserTest extends TestCase
{
    public function testItMapsCreateTableStatementsToTableDefinitions(): void
    {
        $parser = new IamcalSqlParser();

        $sql = <<<'SQL'
CREATE TABLE `accounts` (
    `id` INT NOT NULL,
    `name` VARCHAR(255)
);
SQL;

        $tables = $parser->parseTables($sql);

        $this->assertCount(1, $tables);
        $table = $tables[0];

        $this->assertSame('accounts', $table->name);
        $this->assertCount(2, $table->columns);

        $this->assertSame('id', $table->columns[0]->name);
        $this->assertSame('INT', $table->columns[0]->type);
        $this->assertFalse($table->columns[0]->nullable);

        $this->assertSame('name', $table->columns[1]->name);
        $this->assertSame('VARCHAR', $table->columns[1]->type);
        $this->assertTrue($table->columns[1]->nullable);
    }

    public function testItCorrectlyHandlesNullableColumnsWithDefaultValues(): void
    {
        $parser = new IamcalSqlParser();

        $sql = <<<'SQL'
CREATE TABLE `test_table` (
    `id` int NOT NULL AUTO_INCREMENT,
    `action_value` float DEFAULT 0,
    `fba_vat` float DEFAULT NULL,
    `not_null_col` int NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`)
);
SQL;

        $tables = $parser->parseTables($sql);

        $this->assertCount(1, $tables);
        $table = $tables[0];

        $this->assertSame('test_table', $table->name);
        $this->assertCount(4, $table->columns);

        $this->assertSame('id', $table->columns[0]->name);
        $this->assertFalse($table->columns[0]->nullable);

        $this->assertSame('action_value', $table->columns[1]->name);
        $this->assertTrue($table->columns[1]->nullable, 'Column with DEFAULT 0 but no NOT NULL should be nullable');

        $this->assertSame('fba_vat', $table->columns[2]->name);
        $this->assertTrue($table->columns[2]->nullable);

        $this->assertSame('not_null_col', $table->columns[3]->name);
        $this->assertFalse($table->columns[3]->nullable);
    }

    public function testItHandlesInvalidSqlGracefully(): void
    {
        $parser = new IamcalSqlParser();

        try {
            $result = $parser->parseTables('NOT A VALID SQL');
            $this->assertIsArray($result);
        } catch (SqlParserFailure $e) {
            $this->assertInstanceOf(SqlParserFailure::class, $e);
        }
    }
}

