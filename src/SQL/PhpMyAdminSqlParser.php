<?php

declare(strict_types=1);

namespace Larastan\Larastan\SQL;

use PhpMyAdmin\SqlParser\Components\CreateDefinition;
use PhpMyAdmin\SqlParser\Components\OptionsArray;
use PhpMyAdmin\SqlParser\Exceptions\ParserException;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;

use function is_array;
use function strtolower;

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

        $tables = [];

        foreach ($parser->statements as $statement) {
            if (! $statement instanceof CreateStatement || $statement->name?->table === null) {
                continue;
            }

            $tableName = $statement->name->table;

            if (! is_array($statement->fields)) {
                continue;
            }

            $columns = [];

            foreach ($statement->fields as $field) {
                if ($field->name === null || $field->type === null) {
                    continue;
                }

                $columns[] = new ColumnDefinition(
                    $field->name,
                    $field->type->name,
                    $this->convertTypeOptions($field->type->options),
                    $this->isNullable($field),
                );
            }

            $tables[] = new TableDefinition($tableName, $columns);
        }

        return $tables;
    }

    private function isNullable(CreateDefinition $definition): bool
    {
        return ! $definition->options?->has('NOT NULL');
    }

    /** @return list<lowercase-string> */
    private function convertTypeOptions(OptionsArray $options): array
    {
        $result = [];

        foreach ($options->options as $option) {
            if (is_array($option)) {
                $result[] = strtolower($option['name']);
            } else {
                $result[] = strtolower($option);
            }
        }

        return $result;
    }
}
