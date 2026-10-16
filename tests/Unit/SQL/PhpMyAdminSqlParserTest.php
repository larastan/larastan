<?php

declare(strict_types=1);

namespace Tests\Unit\SQL;

use Larastan\Larastan\SQL\PhpMyAdminSqlParser;
use Larastan\Larastan\SQL\SqlParser;
use Larastan\Larastan\SQL\SqlParserFailure;
use Larastan\Larastan\SQL\TableDefinition;
use PhpMyAdmin\SqlParser\Parser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function array_map;
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

    /** @return iterable<string, array{string}> */
    public static function unparsableSchemas(): iterable
    {
        yield 'parser error' => ['NOT A VALID SQL'];
        yield 'lexer error' => ["CREATE TABLE `accounts` (`id` INT); SELECT 'unterminated;"];
    }

    #[DataProvider('unparsableSchemas')]
    public function testItWrapsParserAndLexerExceptionsWithoutAFallback(string $sql): void
    {
        $this->expectException(SqlParserFailure::class);

        (new PhpMyAdminSqlParser())->parseTables($sql);
    }

    #[DataProvider('unparsableSchemas')]
    public function testItUsesTheFallbackForASchemaItCannotParse(string $sql): void
    {
        $fallback = $this->fallbackParser();

        $tables = (new PhpMyAdminSqlParser($fallback))->parseTables($sql);

        $this->assertSame(['from_fallback'], array_map(static fn (TableDefinition $table): string => $table->name, $tables));
        $this->assertSame([$sql], $fallback->parsedSchemas);
    }

    public function testItDoesNotUseTheFallbackForASchemaItCanParse(): void
    {
        $fallback = $this->fallbackParser();

        $tables = (new PhpMyAdminSqlParser($fallback))->parseTables('CREATE TABLE `accounts` (`id` INT NOT NULL);');

        $this->assertSame(['accounts'], array_map(static fn (TableDefinition $table): string => $table->name, $tables));
        $this->assertSame([], $fallback->parsedSchemas);
    }

    private function fallbackParser(): SqlParser
    {
        return new class implements SqlParser {
            /** @var list<string> */
            public array $parsedSchemas = [];

            /** @inheritDoc */
            public function parseTables(string $sql): array
            {
                $this->parsedSchemas[] = $sql;

                return [new TableDefinition('from_fallback', [])];
            }
        };
    }
}
