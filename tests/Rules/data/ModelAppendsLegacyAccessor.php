<?php

declare(strict_types=1);

namespace Tests\Rules\Data;

use Illuminate\Database\Eloquent\Model;

class UserWithLegacyAccessor extends Model
{
    /** @var array<int, string> */
    protected $appends = ['link'];

    public function getLinkAttribute(): string
    {
        return $this->link();
    }

    public function link(): string
    {
        return 'user/' . $this->slug;
    }
}
