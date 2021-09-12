<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Model;

class GuardedModel extends Model
{
    protected $guarded = ['text'];

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(array $attributes = [])
    {
        $attributes['name'] = $attributes['name'] ?? 'foo';

        parent::__construct($attributes);
    }
}
