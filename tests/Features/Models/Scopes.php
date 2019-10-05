<?php

declare(strict_types=1);

namespace Tests\Features\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Scopes extends Model
{
    public function scopeAfterRelation() : Builder
    {
        return $this->hasOne(User::class)->active();
    }
}
