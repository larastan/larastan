<?php

namespace App\Shapes;

use Database\Factories\Shapes\PolygonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Polygon extends Model
{
    /** @use HasFactory<PolygonFactory> */
    use HasFactory;

    /** @return PolygonFactory<static>|null */
    protected static function newFactory(): PolygonFactory|null
    {
        if (static::class !== self::class) {
            return null;
        }

        return PolygonFactory::new();
    }
}
