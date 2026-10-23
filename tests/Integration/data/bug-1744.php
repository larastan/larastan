<?php

namespace Bug1744;

use App\ModelWithDateColumnCasts;
use Carbon\Carbon;

function test(ModelWithDateColumnCasts $model): void
{
    $model->created_at = '2024-01-01 00:00:00';
    $model->created_at = Carbon::now();
    $model->created_at = null;
    $model->created_at = ['raw' => '2024-01-01 00:00:00', 'formatted' => null];

    $model->updated_at = Carbon::now();
    $model->updated_at = '2024-01-01 00:00:00';

    $model->deleted_at = Carbon::now();
    $model->deleted_at = '2024-01-01 00:00:00';
    $model->deleted_at = null;
}
