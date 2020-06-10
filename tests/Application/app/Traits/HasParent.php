<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasParent
{
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class);
    }
}
