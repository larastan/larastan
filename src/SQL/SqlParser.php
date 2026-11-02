<?php

declare(strict_types=1);

namespace Larastan\Larastan\SQL;

interface SqlParser
{
    /**
     * @return list<TableDefinition>
     *
     * @throws SqlParserFailure
     */
    public function parseTables(string $sql): array;
}
