<?php

declare(strict_types=1);

namespace Tests\Unit\SQL;

use Larastan\Larastan\SQL\ColumnDefinition;
use Larastan\Larastan\SQL\IamcalSqlParser;
use Larastan\Larastan\SQL\PhpMyAdminSqlParser;
use Larastan\Larastan\SQL\SqlParserFactory;
use PhpMyAdmin\SqlParser\Parser;
use PHPUnit\Framework\TestCase;

use function array_map;
use function class_exists;

final class SqlParserFactoryTest extends TestCase
{
    public function testFactoryReturnsDefaultParserWhenOptionalUnavailable(): void
    {
        $defaultParser = new IamcalSqlParser();

        $factory = new SqlParserFactory(
            $defaultParser,
            static fn (): bool => false,
        );

        $this->assertSame($defaultParser, $factory->create());
    }

    public function testFactoryReturnsAdapterWhenOptionalAvailable(): void
    {
        $defaultParser = new IamcalSqlParser();

        $factory = new SqlParserFactory(
            $defaultParser,
            static fn (): bool => true,
        );

        $parser = $factory->create();

        $this->assertInstanceOf(PhpMyAdminSqlParser::class, $parser);
        $this->assertSame($parser, $factory->create());
    }

    public function testOptionalParserFallsBackToDefaultWhenItCannotParseTheDump(): void
    {
        if (! class_exists(Parser::class)) {
            $this->markTestSkipped('phpmyadmin/sql-parser is not installed.');
        }

        $factory = new SqlParserFactory(
            new IamcalSqlParser(),
            static fn (): bool => true,
        );

        $sql = <<<'SQL'
DROP SEQUENCE IF EXISTS `invoice_seq`;
CREATE SEQUENCE `invoice_seq` start with 1 minvalue 1 maxvalue 9223372036854775806 increment by 1 cache 1000 nocycle ENGINE=InnoDB;
CREATE TABLE `accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
SQL;

        $tables = $factory->create()->parseTables($sql);

        $this->assertCount(1, $tables);
        $this->assertSame('accounts', $tables[0]->name);
        $this->assertSame(['id', 'name'], array_map(static fn (ColumnDefinition $column): string => $column->name, $tables[0]->columns));
    }
}
