<?php

declare(strict_types=1);

namespace App;

use App\Casts\FormattedDateCast;
use Illuminate\Database\Eloquent\Model;

class ModelWithFormattedDateCast extends Model
{
    protected $table = 'model_with_casts';

    /** @return array<string, string> */
    public function casts(): array
    {
        return [
            'created_at' => FormattedDateCast::class,
        ];
    }
}
