<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties\Schema;

final class MySqlDataTypeToPhpTypeConverter
{
    public function convert(string $type): string
    {
        return match ($type) {
            'CHAR', 'VARCHAR', 'TINYTEXT', 'TEXT', 'MEDIUMTEXT', 'LONGTEXT', 'BINARY', 'VARBINARY', 'DATE', 'DATETIME', 'TIMESTAMP', 'TIME', 'TINYBLOB', 'BLOB', 'MEDIUMBLOB', 'JSON' => 'string',
            'BIT', 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'INTEGER', 'BIGINT', 'YEAR' => 'int',
            'DECIMAL', 'DEC', 'NUMERIC', 'FIXED', 'FLOAT', 'DOUBLE', 'DOUBLE PRECISION', 'REAL' => 'float',
            'BOOL', 'BOOLEAN' => 'bool',
            default => 'mixed',
        };
    }
}
