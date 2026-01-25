<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties\Schema;

use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;

use function array_map;
use function in_array;

final class MySqlDataTypeToPhpTypeConverter
{
    /**
     * @param list<lowercase-string> $options
     * @param list<string>           $values
     */
    public function convert(string $type, array $options, array $values = []): string
    {
        return match ($type) {
            'CHAR', 'VARCHAR', 'TINYTEXT', 'TEXT', 'MEDIUMTEXT', 'LONGTEXT', 'BINARY', 'VARBINARY', 'DATE', 'DATETIME', 'TIMESTAMP', 'TIME', 'TINYBLOB', 'BLOB', 'MEDIUMBLOB', 'JSON' => 'string',
            'BIT', 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'INTEGER', 'BIGINT', 'YEAR' => in_array('unsigned', $options, true) ? 'non-negative-int' : 'int',
            'DECIMAL', 'DEC', 'NUMERIC', 'FIXED', 'FLOAT', 'DOUBLE', 'DOUBLE PRECISION', 'REAL' => 'float',
            'BOOL', 'BOOLEAN' => 'bool',
            'ENUM' => (static function () use ($values): string {
                return TypeCombinator::union(...array_map(
                    static fn (string $value): ConstantStringType => new ConstantStringType($value),
                    $values,
                ))->describe(VerbosityLevel::value());
            })(),
            default => 'mixed',
        };
    }
}
