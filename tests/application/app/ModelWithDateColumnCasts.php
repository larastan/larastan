<?php

declare(strict_types=1);

namespace App;

use App\Casts\FormattedDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModelWithDateColumnCasts extends Model
{
    use SoftDeletes;

    protected $table = 'model_with_casts';

    /** @var array<string, string> */
    protected $casts = [
        'created_at' => FormattedDate::class,
        'updated_at' => 'timestamp',
        'deleted_at' => 'immutable_datetime',
    ];
}
