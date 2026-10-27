<?php

namespace App\Shapes;

use Database\Factories\Shapes\ShapeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shape extends Model
{
    /** @use HasFactory<ShapeFactory> */
    use HasFactory;
}
