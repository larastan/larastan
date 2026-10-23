<?php

namespace ModelDateColumnCasts;

use App\ModelWithDateColumnCasts;

use function PHPStan\Testing\assertType;

function test(ModelWithDateColumnCasts $model): void
{
    assertType('array{raw: string|null, formatted: string|null}|null', $model->created_at);
    assertType('int|null', $model->updated_at);
    assertType('Carbon\CarbonImmutable|null', $model->deleted_at);
}
