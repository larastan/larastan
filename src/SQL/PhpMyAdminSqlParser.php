<?php

declare(strict_types=1);

namespace Larastan\Larastan\SQL;

use PhpMyAdmin\SqlParser\Components\CreateDefinition;
use PhpMyAdmin\SqlParser\Exceptions\ParserException;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;

use function array_filter;
use function is_array;

final class PhpMyAdminSqlParser implements SqlParser
{
    /** @inheritDoc */
    public function parseTables(string $sql): array
    {
        try {
            $parser = new Parser($sql, true);
        } catch (ParserException $exception) {
            throw SqlParserFailure::create('Failed to parse SQL schema with phpmyadmin/sql-parser.', $exception);
        }

        $createStatements = array_filter(
            $parser->statements,
            static fn (object $statement): bool => $statement instanceof CreateStatement
                && $statement->name?->table !== null,
        );

        $tables = [];

        foreach ($createStatements as $statement) {
            $tableName = $statement->name->table;

            if (! is_array($statement->fields)) {
                continue;
            }

            $columns = [];

            foreach ($statement->fields as $field) {
                if (! $field instanceof CreateDefinition) {
                    continue;
                }

                if ($field->name === null || $field->type === null) {
                    continue;
                }

                $columns[] = new ColumnDefinition(
                    $field->name,
                    $field->type->name,
                    $this->isNullable($field),
                );
            }

            $tables[] = new TableDefinition($tableName, $columns);
        }

        return $tables;
    }

    /** @param CreateDefinition $definition */
    private function isNullable(object $definition): bool
    {
        return ! $definition->options?->has('NOT NULL');
    }
}
