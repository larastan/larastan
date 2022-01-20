<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $keyType = 'uuid';

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @param  array<int, Role>  $models
     * @return RoleCollection<int, Role>
     */
    public function newCollection(array $models = []): RoleCollection
    {
        return new RoleCollection($models);
    }
}
