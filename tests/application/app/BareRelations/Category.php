<?php

namespace App\BareRelations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Relations here are deliberately left without generic annotations. */
class Category extends Model
{
    public function labels(): HasMany
    {
        return $this->hasMany(Label::class);
    }
}
