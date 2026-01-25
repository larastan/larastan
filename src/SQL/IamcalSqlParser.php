<?php

declare(strict_types=1);

namespace Larastan\Larastan\SQL;

use iamcal\SQLParser as VendorIamcalSqlParser;
use iamcal\SQLParserSyntaxException;

use function array_key_exists;
use function in_array;
use function is_array;
use function is_string;

final class IamcalSqlParser implements SqlParser
{
    /** @inheritDoc */
    public function parseTables(string $sql): array
    {
        $parser                      = new VendorIamcalSqlParser();
        $parser->throw_on_bad_syntax = true; // @phpcs:ignore

        try {
            $tableDefinitions = $parser->parse($sql);
        } catch (SQLParserSyntaxException $exception) {
            throw SqlParserFailure::create('Failed to parse SQL schema with iamcal/sql-parser.', $exception);
        }

        $tables = [];
        foreach ($tableDefinitions as $definition) {
            $tableName = $definition['name'] ?? null;
            if (! is_string($tableName)) {
                continue;
            }

            $fields = $definition['fields'] ?? null;
            if (! is_array($fields)) {
                continue;
            }

            $columns = [];
            foreach ($fields as $field) {
                $fieldName = $field['name'] ?? null;
                if (! is_string($fieldName)) {
                    continue;
                }

                $fieldType = $field['type'] ?? null;
                if (! is_string($fieldType)) {
                    continue;
                }

                $columns[] = new ColumnDefinition(
                    $fieldName,
                    $fieldType,
                    $this->resolveTypeOptions($field),
                    $this->resolveNullable($field),
                    $field['values'] ?? [],
                );
            }

            $tables[] = new TableDefinition($tableName, $columns);
        }

        return $tables;
    }

    /**
     * @param array<string, mixed> $field
     *
     * @return list<lowercase-string>
     */
    private function resolveTypeOptions(array $field): array
    {
        $result = [];

        if (array_key_exists('unsigned', $field) && $field['unsigned']) {
            $result[] = 'unsigned';
        }

        return $result;
    }

    /** @param array<string, mixed> $field */
    private function resolveNullable(array $field): bool
    {
        // If the parser explicitly captured NULL / NOT NULL, trust it.
        if (isset($field['null'])) {
            return $field['null'];
        }

        // Types where MySQL generally omits DEFAULT NULL in SHOW CREATE TABLE,
        // but the column is still nullable unless NOT NULL is explicitly present.
        return in_array($field['type'], [
            'TEXT',
            'TINYTEXT',
            'MEDIUMTEXT',
            'LONGTEXT',
            'BLOB',
            'TINYBLOB',
            'MEDIUMBLOB',
            'LONGBLOB',
            'JSON',
            'GEOMETRY',
            'POINT',
            'LINESTRING',
            'POLYGON',
            'MULTIPOINT',
            'MULTILINESTRING',
            'MULTIPOLYGON',
            'GEOMETRYCOLLECTION',
        ], true);
    }
}
