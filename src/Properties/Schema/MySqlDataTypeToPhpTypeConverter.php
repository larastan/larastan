<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties\Schema;

use function in_array;

final class MySqlDataTypeToPhpTypeConverter
{
    /** @param list<lowercase-string> $options */
    public function convert(string $type, array $options): string
    {
        return match ($type) {
            'CHAR', 'VARCHAR', 'TINYTEXT', 'TEXT', 'MEDIUMTEXT', 'LONGTEXT', 'BINARY', 'VARBINARY', 'DATE', 'DATETIME', 'TIMESTAMP', 'TIME', 'TINYBLOB', 'BLOB', 'MEDIUMBLOB', 'JSON' => 'string',
            'BIT', 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'INTEGER', 'BIGINT', 'YEAR' => in_array('unsigned', $options, true) ? 'non-negative-int' : 'int',
            'DECIMAL', 'DEC', 'NUMERIC', 'FIXED', 'FLOAT', 'DOUBLE', 'DOUBLE PRECISION', 'REAL' => 'float',
            'BOOL', 'BOOLEAN' => 'bool',
            default => 'mixed',
        };
    }
}
