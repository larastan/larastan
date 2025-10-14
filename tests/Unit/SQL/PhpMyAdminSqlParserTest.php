<?php

declare(strict_types=1);

namespace Tests\Unit\SQL;

use Larastan\Larastan\SQL\PhpMyAdminSqlParser;
use Larastan\Larastan\SQL\SqlParserFailure;
use PhpMyAdmin\SqlParser\Parser;
use PHPUnit\Framework\TestCase;

use function class_exists;

final class PhpMyAdminSqlParserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(Parser::class)) {
            $this->markTestSkipped('phpmyadmin/sql-parser is not installed.');

            return;
        }
    }

    public function testItMapsCreateTableStatementsToTableDefinitions(): void
    {
        $parser = new PhpMyAdminSqlParser();

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

    public function testItWrapsParserExceptions(): void
    {
        $this->expectException(SqlParserFailure::class);

        $parser = new PhpMyAdminSqlParser();
        $parser->parseTables('NOT A VALID SQL');
    }
}
