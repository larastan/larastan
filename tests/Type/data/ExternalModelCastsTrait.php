<?php

declare(strict_types=1);

namespace ModelCastsParsing\External;

use Illuminate\Database\Eloquent\Casts\AsStringable;

trait ExternalModelCastsTrait
{
    protected function casts(): array
    {
        return [
            'integer' => 'bool',
            'string' => AsStringable::class,
        ];
    }
}
