<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model
{
    use HasFactory;

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return \App\PostBuilder<\App\Post>
     */
    public static function query(): PostBuilder
    {
        return parent::query();
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \App\PostBuilder<\App\Post>
     */
    public function newEloquentBuilder($query): PostBuilder
    {
        return new PostBuilder($query);
    }
}
