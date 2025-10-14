<?php

declare(strict_types=1);

namespace Tests\Unit\SQL;

use Larastan\Larastan\SQL\IamcalSqlParser;
use Larastan\Larastan\SQL\PhpMyAdminSqlParser;
use Larastan\Larastan\SQL\SqlParserFactory;
use PHPUnit\Framework\TestCase;

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
}
