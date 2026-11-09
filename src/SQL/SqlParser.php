<?php

declare(strict_types=1);

namespace Larastan\Larastan\SQL;

use PHPStan\DependencyInjection\AutowiredService;

#[AutowiredService(name: 'sqlParser', factory: '@sqlParserFactory::create')]
interface SqlParser
{
    /**
     * @return list<TableDefinition>
     *
     * @throws SqlParserFailure
     */
    public function parseTables(string $sql): array;
}
