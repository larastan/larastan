<?php

namespace ModelCastsOptOut;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsStringable;

use function PHPStan\Testing\assertType;

class UnparsedModel extends Model
{
    protected $table = 'model_with_casts';

    protected function casts(): array
    {
        return [
            'integer' => 'bool',
            'string' => AsStringable::class,
        ];
    }
}

function test(UnparsedModel $model): void
{
    // Explicit opt-out retains inference from database schema.
    assertType('int', $model->integer);
    assertType('string', $model->string);
}
