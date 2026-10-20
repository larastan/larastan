<?php

namespace ModelCastsParsing;

use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Stringable;
use function PHPStan\Testing\assertType;

function test(ModelWithCasts $model): void
{
    assertType('bool', $model->integer);
    assertType(Stringable::class, $model->string);
    assertType('Carbon\CarbonImmutable|null', $model->created_at);
    assertType('int|null', $model->updated_at);
    assertType('Carbon\Carbon|null', $model->deleted_at);

    $model->created_at = '2024-01-01 00:00:00';
    $model->created_at = \Carbon\CarbonImmutable::now();
    $model->created_at = null;
    $model->deleted_at = '2024-01-01 00:00:00';
    $model->deleted_at = \Carbon\Carbon::now();
}

function testFormattedDateCast(\App\ModelWithFormattedDateCast $model): void
{
    assertType(
        'array{raw: string|null, formatted: string|null, ago: string|null}|null',
        $model->created_at,
    );

    // The cast setter declares the array shape. created_at is also a date column, so a
    // string assignment stays legal: Eloquent runs fromDateTime() before the class cast.
    $model->created_at = [
        'raw' => '2024-01-01 00:00:00',
        'formatted' => '1st Jan 2024 0:00',
        'ago' => '1 year ago',
    ];
    $model->created_at = '2024-01-01 00:00:00';
    $model->created_at = null;
}

class ModelWithCasts extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    public function foo(): int
    {
        return 5;
    }

    public function casts(): array
    {
        return [
            'integer' => 'bool',
            'string' => AsStringable::class.':argument',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'timestamp',
        ];
    }
}
